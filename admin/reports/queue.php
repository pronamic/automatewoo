<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Report_Queue
 */
class Report_Queue extends Admin_List_Table {

	/**
	 * Report name.
	 *
	 * @var string
	 */
	public $name = 'queue';

	/**
	 * Per-render cache of unsubscribe checks, keyed by "workflowId:customerId".
	 *
	 * @var array
	 */
	protected $customer_unsubscribed_cache = [];


	/**
	 * Report_Queue constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Event', 'automatewoo' ),
				'plural'   => __( 'Events', 'automatewoo' ),
				'ajax'     => false,
			]
		);
	}


	/**
	 * Output the report filters.
	 */
	public function filters() {
		$this->output_workflow_filter();
		$this->output_customer_filter();
		$this->output_failed_filter();
	}

	/**
	 * Output the failed status filter.
	 */
	public function output_failed_filter() {
		$selected = Clean::string( aw_request( 'filter_failed' ) );
		?>

		<select name="filter_failed" aria-label="<?php esc_attr_e( 'Filter by queue status', 'automatewoo' ); ?>">
			<option value=""><?php esc_html_e( 'All queue statuses', 'automatewoo' ); ?></option>
			<option value="not_failed" <?php selected( $selected, 'not_failed' ); ?>><?php esc_html_e( 'Not failed', 'automatewoo' ); ?></option>
			<option value="failed" <?php selected( $selected, 'failed' ); ?>><?php esc_html_e( 'Failed', 'automatewoo' ); ?></option>
		</select>

		<?php
	}


	/**
	 * @param Queued_Event $queued_event
	 * @return string
	 */
	public function column_cb( $queued_event ) {
		$id = absint( $queued_event->get_id() );
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label><input id="cb-select-%1$d" type="checkbox" name="queued_event_ids[]" value="%1$d" />',
			$id,
			/* translators: %d: queued event ID */
			esc_html( sprintf( __( 'Select queued event %d', 'automatewoo' ), $id ) )
		);
	}


	/**
	 * @param Queued_Event $event
	 * @param mixed        $column_name
	 * @return string
	 */
	public function column_default( $event, $column_name ) {

		$workflow = $event->get_workflow();

		switch ( $column_name ) {

			case 'queued_event_id':
				echo '#' . esc_html( $event->get_id() );
				if ( $event->is_failed() ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin::badge returns trusted, pre-escaped markup.
					echo Admin::badge( 'warning', 'warning', __( 'Failed', 'automatewoo' ) . ' - ' . $event->get_failure_message() );
				}
				break;

			case 'workflow':
				return $this->format_workflow_title( $workflow );

			case 'date':
				$due_date = $event->get_date_due();
				if ( ! $due_date ) {
					return $this->format_blank();
				}

				if ( $due_date->getTimestamp() > time() ) {
					return $this->format_date( $due_date );
				} else {
					return __( 'now', 'automatewoo' );
				}

			case 'actions':
				$modal_url = add_query_arg(
					[
						'action'          => 'aw_modal_queue_info',
						'queued_event_id' => $event->get_id(),
					],
					admin_url( 'admin-ajax.php' )
				);

				$run_url = wp_nonce_url(
					add_query_arg(
						[
							'action'          => 'run_now',
							'queued_event_id' => $event->get_id(),
						]
					),
					$this->nonce_action
				);

				?>
				<a class="button view aw-button-icon js-open-automatewoo-modal" href="<?php echo esc_url( $modal_url ); ?>"><?php esc_html_e( 'View', 'automatewoo' ); ?></a>
				<a class="button" href="<?php echo esc_url( $run_url ); ?>"><?php $event->is_failed() ? esc_html_e( 'Retry', 'automatewoo' ) : esc_html_e( 'Run Now', 'automatewoo' ); ?></a>
				<?php

				break;

		}
	}


	/**
	 * @param Queued_Event $queued_event
	 *
	 * @return string
	 */
	public function column_customer( $queued_event ) {
		$customer = $queued_event->get_stored_customer();
		if ( $customer ) {
			$workflow = $queued_event->get_workflow();

			if ( $workflow ) {
				return $this->format_customer_with_opt_out_status( $workflow, $customer );
			}

			return Format::customer( $customer );
		}

		$workflow = $queued_event->get_workflow();

		if ( $workflow ) {
			$workflow->set_data_layer( $queued_event->get_data_layer(), true );

			$customer = $workflow->data_layer()->get_customer();
			if ( $customer ) {
				return $this->format_customer_with_opt_out_status( $workflow, $customer );
			} else {
				$guest = $workflow->data_layer()->get_guest();
				if ( $guest ) {
					$customer = Customer_Factory::get_by_guest_id( $guest->get_id() );
					return $this->format_customer_with_opt_out_status( $workflow, $customer );
				}
			}
		}

		return $this->format_blank();
	}


	/**
	 * @param Workflow      $workflow
	 * @param Customer|bool $customer
	 *
	 * @return string
	 */
	protected function format_customer_with_opt_out_status( $workflow, $customer ) {
		if ( ! $customer ) {
			return $this->format_blank();
		}

		$output = Format::customer( $customer );

		if ( $this->is_customer_unsubscribed_for_display( $workflow, $customer ) ) {
			$output .= ' ' . Admin::badge( 'warning', 'warning', __( 'The customer is not opted-in to this workflow.', 'automatewoo' ) );
		}

		return $output;
	}


	/**
	 * Whether a customer is unsubscribed for a workflow, memoized per page render.
	 *
	 * Reuses the canonical send-time check so the badge matches actual send behaviour,
	 * including integration hooks like MailPoet's subscription status sync. The result is
	 * cached per workflow/customer pair so listing many queued events for the same
	 * customer does not repeat the (potentially remote) lookup once per row.
	 *
	 * @param Workflow $workflow
	 * @param Customer $customer
	 *
	 * @return bool
	 */
	protected function is_customer_unsubscribed_for_display( $workflow, $customer ) {
		$key = $workflow->get_id() . ':' . $customer->get_id();

		if ( ! isset( $this->customer_unsubscribed_cache[ $key ] ) ) {
			$this->customer_unsubscribed_cache[ $key ] = $workflow->is_customer_unsubscribed( $customer );
		}

		return $this->customer_unsubscribed_cache[ $key ];
	}


	/**
	 * Get_columns function.
	 */
	public function get_columns() {
		$columns = [
			'cb'              => '<input type="checkbox" />',
			'queued_event_id' => __( 'Queued Event', 'automatewoo' ),
			'workflow'        => __( 'Workflow', 'automatewoo' ),
			'customer'        => __( 'Customer', 'automatewoo' ),
			'date'            => __( 'Run Date', 'automatewoo' ),
			'actions'         => '<span class="screen-reader-text">' . esc_html__( 'Actions', 'automatewoo' ) . '</span>',
		];

		return $columns;
	}


	/**
	 * Prepare_items function.
	 */
	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = $this->get_items_per_page( 'automatewoo_queue_per_page' );

		$this->get_items( $current_page, $per_page );

		$this->set_pagination_args(
			[
				'total_items' => $this->max_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->max_items / $per_page ),
			]
		);
	}



	/**
	 * Get Products matching stock criteria
	 *
	 * @param int $current_page
	 * @param int $per_page
	 */
	public function get_items( $current_page, $per_page ) {

		$query = new Queue_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );
		$query->order_by_failed_status_and_date();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter; value sanitized, no state change.
		if ( ! empty( $_GET['_workflow'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter; value sanitized, no state change.
			$query->where_workflow( absint( wp_unslash( $_GET['_workflow'] ) ) );
		}

		$customer_id = absint( aw_request( 'filter_customer' ) );
		if ( $customer_id ) {
			$customer = Customer_Factory::get( $customer_id );
			if ( $customer ) {
				$query->where_customer_or_legacy_user( $customer );
			}
		}

		switch ( Clean::string( aw_request( 'filter_failed' ) ) ) {
			case 'failed':
				$query->where_failed( true );
				break;

			case 'not_failed':
				$query->where_failed( false );
				break;
		}

		$res             = $query->get_results();
		$this->items     = $res;
		$this->max_items = $query->found_rows;
	}


	/**
	 * Retrieve the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk_delete' => __( 'Delete', 'automatewoo' ),
		];

		return $actions;
	}
}

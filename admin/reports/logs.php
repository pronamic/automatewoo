<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Report_Logs
 */
class Report_Logs extends Admin_List_Table {

	/**
	 * List Table slug.
	 *
	 * @var string
	 */
	public $name = 'logs';


	/**
	 * Default constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Log', 'automatewoo' ),
				'plural'   => __( 'Logs', 'automatewoo' ),
				'ajax'     => false,
			]
		);
	}


	/**
	 * Output the table filters.
	 */
	public function filters() {
		$this->output_workflow_filter();
		$this->output_customer_filter();
	}


	/**
	 * Message shown when there are no items.
	 */
	public function no_items() {
		esc_html_e( 'No logs found.', 'automatewoo' );
	}


	/**
	 * @param Log $log
	 * @return string
	 */
	public function column_cb( $log ) {
		$id = absint( $log->get_id() );
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label><input id="cb-select-%1$d" type="checkbox" name="log_ids[]" value="%1$d" />',
			$id,
			/* translators: %d: log ID */
			esc_html( sprintf( __( 'Select log %d', 'automatewoo' ), $id ) )
		);
	}


	/**
	 * @param Log   $log
	 * @param mixed $column_name
	 * @return string
	 */
	public function column_default( $log, $column_name ) {

		switch ( $column_name ) {
			case 'id':
				echo '#' . esc_html( $log->get_id() );
				if ( $log->has_errors() ) {
					echo Admin::badge( 'warning', 'warning', __( 'Errors occurred when running this workflow. See log notes for more info.', 'automatewoo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns trusted price/markup HTML.
				}
				if ( $log->has_blocked_emails() ) {
					echo Admin::badge( 'blocked-email', 'email', __( 'An email was blocked from sending. See log notes for more info.', 'automatewoo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns trusted price/markup HTML.
				}
				break;

			case 'workflow':
				return $this->format_workflow_title( $log->get_workflow() );

			case 'user':
				$data_layer = $log->get_data_layer( 'object' );

				if ( $data_layer->get_customer() ) {
					return Format::customer( $data_layer->get_customer() );
				} elseif ( $data_layer->get_guest() ) {
					return $this->format_guest( $data_layer->get_guest()->get_email() );
				} elseif ( $data_layer->get_user() ) {
					return $this->format_user( $data_layer->get_user() );
				} else {
					return $this->format_blank();
				}
				break;

			case 'time':
				return $this->format_date( $log->get_date() );

			case 'actions':
				$url = add_query_arg(
					[
						'action' => 'aw_modal_log_info',
						'log_id' => $log->get_id(),
					],
					admin_url( 'admin-ajax.php' )
				);

				echo '<a class="button view aw-button-icon js-open-automatewoo-modal" href="' . esc_url( $url ) . '">View</a>';

				break;
		}
	}

	/**
	 * Get_columns function.
	 */
	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'id'       => __( 'Log', 'automatewoo' ),
			'workflow' => __( 'Workflow', 'automatewoo' ),
			'user'     => __( 'Customer', 'automatewoo' ),
			'time'     => __( 'Time', 'automatewoo' ),
			'actions'  => '<span class="screen-reader-text">' . esc_html__( 'Actions', 'automatewoo' ) . '</span>',
		];

		return $columns;
	}


	/**
	 * Prepare_items function.
	 */
	public function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = $this->get_items_per_page( 'automatewoo_logs_per_page' );

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
	 * @param int $current_page
	 * @param int $per_page
	 */
	public function get_items( $current_page, $per_page ) {

		$query = new Log_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );
		$query->set_ordering( 'date', 'DESC' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only report filter; value is absint()-sanitized, no state change.
		if ( ! empty( $_GET['_workflow'] ) ) {
			$query->where_workflow( absint( $_GET['_workflow'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only report filter; value is absint()-sanitized, no state change.
		}

		$customer_id = absint( aw_request( 'filter_customer' ) );
		if ( $customer_id ) {
			$customer = Customer_Factory::get( $customer_id );
			if ( $customer ) {
				$query->where_customer_or_legacy_user( $customer );
			}
		}

		$this->items     = $query->get_results();
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

	/**
	 * Display the table plus the log deletion confirmation script.
	 */
	public function display() {
		parent::display();
		$this->output_delete_confirmation_script();
	}

	/**
	 * Prompt before deleting logs through the bulk action.
	 */
	private function output_delete_confirmation_script() {
		$message = __( 'Deleting workflow logs can affect workflow behavior and cannot be undone. Are you sure you want to delete the selected logs?', 'automatewoo' );
		?>
		<script>
			jQuery( function( $ ) {
				const message = <?php echo wp_json_encode( $message ); ?>;

				$( '.automatewoo-list-table--logs' ).closest( 'form.automatewoo-list-table-form' ).on( 'submit', function( event ) {
					const submitter = event.originalEvent && event.originalEvent.submitter ? event.originalEvent.submitter : document.activeElement;
					const submitterId = submitter ? submitter.getAttribute( 'id' ) : '';

					if ( submitterId && submitterId !== 'doaction' && submitterId !== 'doaction2' ) {
						return;
					}

					const $form = $( this );
					const isDeleteAction =
						$form.find( 'select[name="action"]' ).val() === 'bulk_delete' ||
						$form.find( 'select[name="action2"]' ).val() === 'bulk_delete';
					const hasSelectedLogs = $form.find( 'tbody input[name="log_ids[]"]:checked' ).length > 0;

					if ( isDeleteAction && hasSelectedLogs && ! window.confirm( message ) ) {
						event.preventDefault();
					}
				} );
			} );
		</script>
		<?php
	}
}

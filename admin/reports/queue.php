<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Queue
 */
class Report_Queue extends Admin_List_Table {

	public $name = 'queue';


	function __construct() {
		parent::__construct([
			'singular' => __( 'Event', 'automatewoo' ),
			'plural' => __( 'Events', 'automatewoo' ),
			'ajax' => false
		]);
	}


	function filters() {
		$this->output_workflow_filter();
		$this->output_customer_filter();
		$this->output_failed_filter();
	}

	function output_failed_filter() {
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
	 * @param $queued_event Queued_Event
	 * @return string
	 */
	function column_cb( $queued_event ) {
		$id = absint( $queued_event->get_id() );
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label><input id="cb-select-%1$d" type="checkbox" name="queued_event_ids[]" value="%1$d" />',
			$id,
			/* translators: %d: queued event ID */
			esc_html( sprintf( __( 'Select queued event %d', 'automatewoo' ), $id ) )
		);
	}


	/**
	 * @param $event Queued_Event
	 * @param mixed $column_name
	 * @return string
	 */
	function column_default( $event, $column_name ) {

		$workflow = $event->get_workflow();

		switch( $column_name ) {

			case 'queued_event_id':
				echo '#' . $event->get_id() . '';
				if ( $event->is_failed() ) {
					echo Admin::badge( 'warning', 'warning', __( 'Failed', 'automatewoo' ) . ' - ' . $event->get_failure_message() );
				}
				break;

			case 'workflow':
				return $this->format_workflow_title( $workflow );
				break;

			case 'date':

			    if ( ! $due_date = $event->get_date_due() ) {
			        return $this->format_blank();
                }

				if ( $due_date->getTimestamp() > time() ) {
					return $this->format_date( $due_date );
				}
				else {
					return __( 'now', 'automatewoo' );
				}

				break;

			case 'actions':

                $modal_url = add_query_arg([
                    'action' => 'aw_modal_queue_info',
                    'queued_event_id' => $event->get_id()
                ], admin_url('admin-ajax.php') );

				$run_url = wp_nonce_url(
					add_query_arg([
						'action' => 'run_now',
						'queued_event_id' => $event->get_id()
					]),
					$this->nonce_action
				);

				?>
                <a class="button view aw-button-icon js-open-automatewoo-modal" href="<?php echo $modal_url ?>"><?php _e( 'View', 'automatewoo' ) ?></a>
                <a class="button" href="<?php echo $run_url; ?>"><?php $event->is_failed() ? esc_attr_e( 'Retry', 'automatewoo' ) : esc_attr_e( 'Run Now', 'automatewoo' ) ?></a>
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
		if ( $customer = $queued_event->get_stored_customer() ) {
			return Format::customer( $customer );
		}

		$workflow = $queued_event->get_workflow();

		if ( $workflow ) {
			$workflow->set_data_layer( $queued_event->get_data_layer(), true );

			if ( $customer = $workflow->data_layer()->get_customer() ) {
				return Format::customer( $customer );
			}
			elseif ( $guest = $workflow->data_layer()->get_guest() ) {
				$customer = Customer_Factory::get_by_guest_id( $guest->get_id() );
				return Format::customer( $customer );
			}
		}

		return $this->format_blank();
	}


	/**
	 * get_columns function.
	 */
	function get_columns() {
		$columns = [
			'cb' => '<input type="checkbox" />',
			'queued_event_id' => __( 'Queued Event', 'automatewoo' ),
			'workflow' => __( 'Workflow', 'automatewoo' ),
			'customer' => __( 'Customer', 'automatewoo' ),
			'date' => __( 'Run Date', 'automatewoo' ),
			'actions' => '<span class="screen-reader-text">' . esc_html__( 'Actions', 'automatewoo' ) . '</span>',
		];

		return $columns;
	}


	/**
	 * prepare_items function.
	 */
	function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page = absint( $this->get_pagenum() );
		$per_page = $this->get_items_per_page( 'automatewoo_queue_per_page' );

		$this->get_items( $current_page, $per_page );

		$this->set_pagination_args([
			'total_items' => $this->max_items,
			'per_page' => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page )
		]);
	}



	/**
	 * Get Products matching stock criteria
	 */
	function get_items( $current_page, $per_page ) {

		$query = new Queue_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );
		$query->order_by_failed_status_and_date();

		if ( ! empty( $_GET[ '_workflow' ] ) ) {
			$query->where_workflow( absint( $_GET['_workflow'] ) );
		}

		 if ( $customer_id = absint( aw_request('filter_customer' ) ) ) {
			 if ( $customer = Customer_Factory::get( $customer_id ) ) {
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

		$res = $query->get_results();
		$this->items = $res;
		$this->max_items = $query->found_rows;
	}


	/**
	 * Retrieve the bulk actions
	 */
	function get_bulk_actions() {
		$actions = [
			'bulk_delete' => __( 'Delete', 'automatewoo' ),
		];

		return $actions;
	}

}

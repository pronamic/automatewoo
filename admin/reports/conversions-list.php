<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * @class Report_Conversions_List
 */
class Report_Conversions_List extends Admin_List_Table {

	public $name = 'conversions';


	function __construct() {
		parent::__construct( [
			'singular'  => __( 'Conversion', 'automatewoo' ),
			'plural'    => __( 'Conversions', 'automatewoo' ),
			'ajax'      => false
		]);
	}


	function no_items() {
		_e( 'No conversions found.', 'automatewoo' );
	}


	/**
	 * Retrieve the bulk actions
	 */
	function get_bulk_actions() {
		$actions = [
			'bulk_unmark_conversion' => __( 'Unmark As Conversion', 'automatewoo' ),
		];

		return $actions;
	}


	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	function column_cb( $order ) {
		return '<input type="checkbox" name="order_ids[]" value="' . $order->get_id() . '" />';
	}


	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	function column_interacted( $order ) {
		$log = Log_Factory::get( $order->get_meta( '_aw_conversion_log' ) );

		if ( $log ) {
			return $this->format_date( $log->get_date_opened() );
		}

		return $this->format_blank();
	}


	/**
	 * @param $order \WC_Order
	 * @return string
	 */
	function column_workflow( $order ) {
		if ( $workflow = Factory::get( $order->get_meta( '_aw_conversion' ) ) ) {
			return $this->format_workflow_title( $workflow );
		}

		return $this->format_blank();
	}


	/**
	 * @param \WC_Order $order
	 * @param mixed $column_name
	 * @return mixed
	 */
	function column_default( $order, $column_name ) {

		switch( $column_name ) {
			case 'order':
				return '<a href="' . $order->get_edit_order_url() . '"><strong>#' . $order->get_order_number() . '</strong></a>';

				break;

			case 'customer':

				if ( $user = $order->get_user() )
					return '<a href="' . get_edit_user_link( $user->ID ) . '">' . $user->first_name . ' ' . $user->last_name . '</a>';
				else
					return $order->get_formatted_billing_full_name();

				break;

			case 'order_placed':
				return $this->format_date( $order->get_date_created() );
				break;

			case 'log':

				$log_id = Clean::id( $order->get_meta( '_aw_conversion_log' ) );

				if ( $log_id ) {
					$url = add_query_arg([
						'action' => 'aw_modal_log_info',
						'log_id' => $log_id
					], admin_url('admin-ajax.php') );

					return '<a class="js-open-automatewoo-modal" href="' . $url . '">#'.$log_id.'</a>';
				}
				else {
					return $this->format_blank();
				}

				break;

			case 'total':
				return wc_price( $order->get_total() );
				break;

		}
	}

	/**
	 * get_columns function.
	 */
	function get_columns() {
		$columns = [
			'cb' => '<input type="checkbox" />',
			'order'  => __( 'Order', 'automatewoo' ),
			'customer'  => __( 'Customer', 'automatewoo' ),
			'workflow' => __( 'Workflow', 'automatewoo' ),
			'log' => __( 'Log', 'automatewoo' ),
			'interacted' => __( 'First Interacted', 'automatewoo' ),
			'order_placed' => __( 'Order Placed', 'automatewoo' ),
			'total' => __( 'Order Total', 'automatewoo' ),
		];

		return $columns;
	}

	/**
	 * prepare_items function.
	 */
	function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page = absint( $this->get_pagenum() );
		$per_page = apply_filters( 'automatewoo_report_items_per_page', 20 );

		$this->get_items( $current_page, $per_page );

		/**
		 * Pagination
		 */
		$this->set_pagination_args( [
			'total_items' => $this->max_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page )
		]);
	}



	/**
	 * Get Products matching stock criteria
	 */
	function get_items( $current_page, $per_page ) {

		$query = new \WP_Query([
			'post_type' => 'shop_order',
			'post_status' => array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() ),
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
			'meta_query' => [
				[
					'key' => '_aw_conversion',
					'compare' => 'EXISTS',
				]
			]
		]);

		foreach ( $query->posts as $order ) {
			$this->items[] = wc_get_order( $order );
		}

		$this->max_items = $query->found_posts;
	}

}

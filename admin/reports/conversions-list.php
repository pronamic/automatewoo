<?php

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @class Report_Conversions_List
 */
class Report_Conversions_List extends Admin_List_Table {

	/** @var string */
	public $name = 'conversions';


	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Conversion', 'automatewoo' ),
				'plural'   => __( 'Conversions', 'automatewoo' ),
				'ajax'     => false,
			]
		);
	}


	/**
	 * Message to display when there are no items.
	 */
	public function no_items() {
		esc_html_e( 'No conversions found.', 'automatewoo' );
	}


	/**
	 * Retrieve the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk_unmark_conversion' => __( 'Unmark As Conversion', 'automatewoo' ),
		];

		return $actions;
	}


	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	public function column_cb( $order ) {
		return '<input type="checkbox" name="order_ids[]" value="' . $order->get_id() . '" />';
	}


	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	public function column_interacted( $order ) {
		$log = Log_Factory::get( $order->get_meta( '_aw_conversion_log' ) );

		if ( $log ) {
			return $this->format_date( $log->get_date_opened() );
		}

		return $this->format_blank();
	}


	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	public function column_workflow( $order ) {
		$workflow = Factory::get( $order->get_meta( '_aw_conversion' ) );
		if ( $workflow ) {
			return $this->format_workflow_title( $workflow );
		}

		return $this->format_blank();
	}


	/**
	 * @param \WC_Order $order
	 * @param mixed     $column_name
	 * @return mixed
	 */
	public function column_default( $order, $column_name ) {

		switch ( $column_name ) {
			case 'order':
				return '<a href="' . $order->get_edit_order_url() . '"><strong>#' . $order->get_order_number() . '</strong></a>';

			case 'customer':
				$user = $order->get_user();
				if ( $user ) {
					return '<a href="' . get_edit_user_link( $user->ID ) . '">' . $user->first_name . ' ' . $user->last_name . '</a>';
				} else {
					return $order->get_formatted_billing_full_name();
				}

			case 'order_placed':
				return $this->format_date( $order->get_date_created() );

			case 'log':
				$log_id = Clean::id( $order->get_meta( '_aw_conversion_log' ) );

				if ( $log_id ) {
					$url = add_query_arg(
						[
							'action' => 'aw_modal_log_info',
							'log_id' => $log_id,
						],
						admin_url( 'admin-ajax.php' )
					);

					return '<a class="js-open-automatewoo-modal" href="' . $url . '">#' . $log_id . '</a>';
				} else {
					return $this->format_blank();
				}

			case 'total':
				return wc_price( $order->get_total() );

		}
	}

	/**
	 * Get the report columns.
	 */
	public function get_columns() {
		$columns = [
			'cb'           => '<input type="checkbox" />',
			'order'        => __( 'Order', 'automatewoo' ),
			'customer'     => __( 'Customer', 'automatewoo' ),
			'workflow'     => __( 'Workflow', 'automatewoo' ),
			'log'          => __( 'Log', 'automatewoo' ),
			'interacted'   => __( 'First Interacted', 'automatewoo' ),
			'order_placed' => __( 'Order Placed', 'automatewoo' ),
			'total'        => __( 'Order Total', 'automatewoo' ),
		];

		return $columns;
	}

	/**
	 * Prepare the report items.
	 */
	public function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = apply_filters( 'automatewoo_report_items_per_page', 20 );

		$this->get_items( $current_page, $per_page );

		/**
		 * Pagination
		 */
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

		$query = new \WP_Query(
			[
				'post_type'      => 'shop_order',
				'post_status'    => array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() ),
				'posts_per_page' => $per_page,
				'offset'         => ( $current_page - 1 ) * $per_page,
				'meta_query'     => [
					[
						'key'     => '_aw_conversion',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		foreach ( $query->posts as $order ) {
			$this->items[] = wc_get_order( $order );
		}

		$this->max_items = $query->found_posts;
	}
}

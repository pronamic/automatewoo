<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Carts
 */
class Report_Carts extends Admin_List_Table {

	public $name = 'carts';

	protected $default_param_orderby = 'last_modified';


	function __construct() {
		parent::__construct([
			'singular' => __( 'Cart', 'automatewoo' ),
			'plural' => __( 'Carts', 'automatewoo' ),
			'ajax' => false
		]);
	}

	/**
	 * Display filters.
	 *
	 * @since 5.2.0
	 */
	public function filters() {
		$this->output_customer_filter();
	}

	function get_columns() {

		$columns = [
			'cb' => '<input type="checkbox" />',
			'id' => __( 'Cart', 'automatewoo' ),
			'status' => __( 'Status', 'automatewoo' ),
			'user' => __( 'Customer', 'automatewoo' ),
			'last_modified' => __( 'Last active', 'automatewoo' ),
			'items' => __( 'Items', 'automatewoo' ),
			'total' => __( 'Total', 'automatewoo' ),
			'actions' => '',
		];

		if ( Language::is_multilingual() ) {
			$columns['language'] = __( 'Language', 'automatewoo' );
		}

		return $columns;
	}


	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'last_modified' => [ 'last_modified', true ],
			'total' => [ 'total', true ]
		];
	}


	/**
	 * @param Cart $cart
	 * @param mixed $column_name
	 * @return string
	 */
	function column_default( $cart, $column_name ) {

		switch( $column_name ) {

			case 'id':
				return '#' . $cart->get_id();
				break;

			case 'user':
				return Format::customer( $cart->get_customer() );
				break;

			case 'last_modified':
				return $this->format_date( $cart->get_date_last_modified() );
				break;

			case 'items':
				return $cart->get_item_count();
				break;

			case 'total':
				return $cart->price( $cart->get_total() );
				break;

			case 'language':
				return $cart->get_language();
				break;

			case 'actions':

				$url = add_query_arg([
					'action' => 'aw_modal_cart_info',
					'cart_id' => $cart->get_id()
				], admin_url( 'admin-ajax.php' ) );

				return '<a class="button view aw-button-icon js-open-automatewoo-modal" data-automatewoo-modal-size="lg" href="' . $url . '">View</a>';

				break;
		}
	}


	/**
	 * @param $cart Cart
	 * @return string
	 */
	function column_cb( $cart ) {
		return '<input type="checkbox" name="cart_ids[]" value="' . absint( $cart->get_id() ) . '" />';
	}


	/**
	 * @param $cart Cart
	 * @return string
	 */
	function column_status( $cart ) {
		return Carts::get_statuses()[$cart->get_status()];
	}


	/**
	 * prepare_items function.
	 */
	function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page = absint( $this->get_pagenum() );
		$per_page = $this->get_items_per_page( 'automatewoo_carts_per_page' );

		$this->get_items( $current_page, $per_page );

		$this->set_pagination_args([
			'total_items' => $this->max_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page )
		]);
	}



	/**
	 * Get Products matching stock criteria
	 */
	function get_items( $current_page, $per_page ) {

		$query = new Cart_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );
		$query->set_ordering( $this->get_param_orderby(), $this->get_param_order(), array_keys( $this->get_sortable_columns() ) );

		// Filter items based on customer.
		$customer_id = absint( aw_request( 'filter_customer' ) );
		if ( $customer_id ) {
			$customer = Customer_Factory::get( $customer_id );
			if ( $customer instanceof Customer ) {
				$query->where_customer( $customer );
			}
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
			'bulk_mark_active' => __( 'Change status to active', 'automatewoo' ),
			'bulk_mark_abandoned' => __( 'Change status to abandoned', 'automatewoo' ),
			'bulk_delete' => __( 'Delete', 'automatewoo' ),
		];

		return $actions;
	}

}

<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Guests
 */
class Report_Guests extends Admin_List_Table {

	public $name = 'guests';

	public $enable_search = true;

	protected $default_param_orderby = 'last_active';


	function __construct() {
		parent::__construct([
			'singular' => __( 'Guest', 'automatewoo' ),
			'plural' => __( 'Guests', 'automatewoo' ),
			'ajax' => false
		]);
		$this->search_button_text = __( 'Search guests', 'automatewoo' );
	}


	function no_items() {
		_e( 'No guests found.', 'automatewoo' );
	}


	/**
	 * get_columns function.
	 */
	function get_columns() {
		$columns = [
			'cb' => '<input type="checkbox" />',
			'id' => __( 'Guest', 'automatewoo' ),
			'email' => __( 'Email', 'automatewoo' ),
			'last_active' => __( 'Last Active', 'automatewoo' ),
			'created' => __( 'Created', 'automatewoo' ),
			'actions' => __( 'Actions', 'automatewoo' )
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
			'last_active' => [ 'last_active', true ],
			'created' => [ 'created', true ]
		];
	}


	function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page = absint( $this->get_pagenum() );
		$per_page = $this->get_items_per_page( 'automatewoo_guests_per_page' );

		$this->get_items( $current_page, $per_page );

		$this->set_pagination_args([
			'total_items' => $this->max_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page )
		]);
	}



	function get_items( $current_page, $per_page ) {

		$query = new Guest_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );
		$query->set_ordering( $this->get_param_orderby(), $this->get_param_order(), array_keys( $this->get_sortable_columns() ) );

		if ( $search = $this->get_param_search() ) {
			$key = strtolower( $search );
			$query->where('email', "%$key%", 'LIKE' );
		}

		$res = $query->get_results();

		$this->items = $res;

		$this->max_items = $query->found_rows;
	}


	/**
	 * @param $guest Guest
	 * @return string
	 */
	function column_cb( $guest ) {
		return '<input type="checkbox" name="guest_ids[]" value="' . absint( $guest->get_id() ) . '" />';
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	function column_id( $guest ) {
		return '#' . $guest->get_id();
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	function column_email( $guest ) {
		return "<a href='mailto:{$guest->get_email()}'>{$guest->get_email()}</a>";
	}


	/**
	 * @param Guest $guest
	 * @return false|string
	 */
	function column_created( $guest ) {
		return $this->format_date( $guest->get_date_created() );
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	function column_last_active( $guest ) {
		return $this->format_date( $guest->get_date_last_active() );
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	function column_language( $guest ) {
		return Language::get_guest_language( $guest );
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	function column_actions( $guest ) {

		$actions = [];

		$link = Admin::page_url('guest', $guest->get_id() );
		$actions['view'] = '<a href="'. $link .'">'.__( 'View', 'automatewoo' ).'</a>';

		$link = wp_nonce_url( add_query_arg( ['action' => 'delete', 'guest_id' => $guest->get_id() ], Admin::page_url('guests' ) ), $this->nonce_action );
		$actions['delete'] = '<a href="'. $link .'">'.__( 'Delete', 'automatewoo' ).'</a>';

		return $this->row_actions( $actions, true );
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

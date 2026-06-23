<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Report_Optins
 */
class Report_Optins extends Admin_List_Table {

	/**
	 * List Table slug
	 *
	 * @var string
	 */
	public $name = 'opt-ins';

	/**
	 * Enable searching in list table.
	 *
	 * @var bool
	 */
	public $enable_search = true;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$showing_optouts = $this->is_showing_optouts();

		parent::__construct(
			[
				'singular' => $showing_optouts ? __( 'Opt-out', 'automatewoo' ) : __( 'Opt-in', 'automatewoo' ),
				'plural'   => $showing_optouts ? __( 'Opt-outs', 'automatewoo' ) : __( 'Opt-ins', 'automatewoo' ),
				'ajax'     => false,
			]
		);
		$this->search_button_text = __( 'Search by email', 'automatewoo' );
	}

	/**
	 * Is the table showing opt-outs?
	 *
	 * @return bool
	 */
	protected function is_showing_optouts() {
		return ! Options::optin_enabled() || 'optouts' === aw_request( 'optin_status' );
	}

	/**
	 * Preserve the selected opt-in status view in list table forms.
	 *
	 * @return void
	 */
	public function output_form_open() {
		parent::output_form_open();

		if ( Options::optin_enabled() && $this->is_showing_optouts() ) {
			echo '<input type="hidden" name="optin_status" value="optouts" />';
		}
	}

	/**
	 * Get status views for opt-in mode.
	 *
	 * @return array
	 */
	protected function get_views() {
		if ( ! Options::optin_enabled() ) {
			return [];
		}

		$showing_optouts = $this->is_showing_optouts();

		return [
			'optins'  => sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( Admin::page_url( 'opt-ins' ) ),
				$showing_optouts ? '' : 'current',
				esc_html__( 'Opt-ins', 'automatewoo' )
			),
			'optouts' => sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( add_query_arg( 'optin_status', 'optouts', Admin::page_url( 'opt-ins' ) ) ),
				$showing_optouts ? 'current' : '',
				esc_html__( 'Opt-outs', 'automatewoo' )
			),
		];
	}

	/**
	 * @param Customer $customer
	 * @return string
	 */
	public function column_cb( $customer ) {
		$id = absint( $customer->get_id() );
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label><input id="cb-select-%1$d" type="checkbox" name="customer_ids[]" value="%1$d" />',
			$id,
			/* translators: %d: customer ID */
			esc_html( sprintf( __( 'Select customer %d', 'automatewoo' ), $id ) )
		);
	}

	/**
	 * @param Customer $customer
	 * @param mixed    $column_name
	 * @return string
	 */
	public function column_default( $customer, $column_name ) {
		switch ( $column_name ) {
			case 'email':
				return Format::customer( $customer );

			case 'time':
				return $this->format_date( $this->is_showing_optouts() ? $customer->get_date_unsubscribed() : $customer->get_date_subscribed() );

			case 'workflow':
				return $this->format_workflow( $customer->get_unsubscribed_workflow_id() );
		}
	}

	/**
	 * Format workflow column.
	 *
	 * @param int $workflow_id
	 * @return string
	 */
	protected function format_workflow( $workflow_id ) {
		$workflow_id = Clean::id( $workflow_id );
		if ( ! $workflow_id ) {
			return '&mdash;';
		}

		$title = get_the_title( $workflow_id );
		$text  = $title ? $title : sprintf(
			/* translators: %d Workflow ID. */
			__( 'Workflow #%d', 'automatewoo' ),
			$workflow_id
		);
		$url = get_edit_post_link( $workflow_id, 'raw' );

		if ( ! $url ) {
			return esc_html( $text );
		}

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $text ) );
	}

	/**
	 * Get columns for the list table.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'    => '<input type="checkbox" />',
			'email' => __( 'Customer', 'automatewoo' ),
			'time'  => __( 'Date', 'automatewoo' ),
		];

		if ( $this->is_showing_optouts() ) {
			$columns['workflow'] = __( 'Workflow', 'automatewoo' );
		}

		return $columns;
	}

	/**
	 * Retrieve the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = [];

		if ( $this->is_showing_optouts() ) {
			$actions['bulk_optin'] = __( 'Set as opted-in', 'automatewoo' );
		} else {
			$actions['bulk_optout'] = __( 'Set as opted-out', 'automatewoo' );
		}

		return $actions;
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = $this->get_items_per_page( 'automatewoo_optins_per_page' );

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
	 * Fetch list of items from DB.
	 *
	 * @param string $current_page
	 * @param int    $per_page
	 */
	public function get_items( $current_page, $per_page ) {
		$query = new Customer_Query();

		if ( $this->is_showing_optouts() ) {
			$query->where( 'unsubscribed', true );
			$query->set_ordering( 'unsubscribed_date' );
		} else {
			$query->where( 'subscribed', true );
			$query->set_ordering( 'subscribed_date' );
		}

		$has_no_valid_search_matches = false;

		if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$search        = trim( strtolower( Clean::string( $_GET['s'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
			$search_wheres = [];

			$guest_query = new Guest_Query();
			$guest_query->where( 'email', "%$search%", 'LIKE' );
			$guest_ids = $guest_query->get_results_as_ids();

			if ( $guest_ids ) {
				$search_wheres[] = [
					'column'  => 'guest_id',
					'value'   => $guest_ids,
					'compare' => 'IN',
				];
			}

			$user_query = new \WP_User_Query(
				[
					'search'         => '*' . esc_attr( $search ) . '*',
					'search_columns' => [ 'user_email' ],
					'fields'         => 'ID',
				]
			);

			$user_ids = $user_query->get_results();

			if ( $user_ids ) {
				$search_wheres[] = [
					'column'  => 'user_id',
					'value'   => $user_ids,
					'compare' => 'IN',
				];
			}

			if ( $search_wheres ) {
				$query->where[] = $search_wheres;
			} else {
				$has_no_valid_search_matches = true;
			}
		}

		$query->set_calc_found_rows( true );
		$query->set_limit( $per_page );
		$query->set_page( $current_page );

		// if there are no valid search matches there are no matching customers
		if ( $has_no_valid_search_matches === false ) {
			$results         = $query->get_results();
			$this->items     = $results;
			$this->max_items = $query->found_rows;
		}
	}
}

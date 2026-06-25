<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Report_Carts
 */
class Report_Carts extends Admin_List_Table {

	/**
	 * @var string
	 */
	public $name = 'carts';

	/**
	 * @var string
	 */
	protected $default_param_orderby = 'last_modified';


	/**
	 * Report_Carts constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Cart', 'automatewoo' ),
				'plural'   => __( 'Carts', 'automatewoo' ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Display filters.
	 *
	 * @since 5.2.0
	 */
	public function filters() {
		$this->output_customer_filter();
		$this->output_status_filter();
	}

	/**
	 * Get the columns for the list table.
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = [
			'cb'            => '<input type="checkbox" />',
			'id'            => __( 'Cart', 'automatewoo' ),
			'status'        => __( 'Status', 'automatewoo' ),
			'user'          => __( 'Customer', 'automatewoo' ),
			'last_modified' => __( 'Last active', 'automatewoo' ),
			'items'         => __( 'Items', 'automatewoo' ),
			'total'         => __( 'Total', 'automatewoo' ),
			'actions'       => '<span class="screen-reader-text">' . esc_html__( 'Actions', 'automatewoo' ) . '</span>',
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
			'total'         => [ 'total', true ],
		];
	}


	/**
	 * @param Cart  $cart
	 * @param mixed $column_name
	 * @return string
	 */
	public function column_default( $cart, $column_name ) {

		switch ( $column_name ) {

			case 'id':
				return '#' . $cart->get_id();

			case 'user':
				return Format::customer( $cart->get_customer() );

			case 'last_modified':
				return $this->format_date( $cart->get_date_last_modified() );

			case 'items':
				return $cart->get_item_count();

			case 'total':
				return $cart->price( $cart->get_total() );

			case 'language':
				return $cart->get_language();

			case 'actions':
				$url = add_query_arg(
					[
						'action'  => 'aw_modal_cart_info',
						'cart_id' => $cart->get_id(),
					],
					admin_url( 'admin-ajax.php' )
				);

				return '<a class="button view aw-button-icon js-open-automatewoo-modal" data-automatewoo-modal-size="lg" href="' . $url . '">View</a>';
		}
	}


	/**
	 * @param Cart $cart
	 * @return string
	 */
	public function column_cb( $cart ) {
		$id = absint( $cart->get_id() );
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label><input id="cb-select-%1$d" type="checkbox" name="cart_ids[]" value="%1$d" />',
			$id,
			/* translators: %d: cart ID */
			esc_html( sprintf( __( 'Select cart %d', 'automatewoo' ), $id ) )
		);
	}


	/**
	 * @param Cart $cart
	 * @return string
	 */
	public function column_status( $cart ) {
		$statuses = Carts::get_statuses();

		return isset( $statuses[ $cart->get_status() ] ) ? $statuses[ $cart->get_status() ] : $cart->get_status();
	}


	/**
	 * Prepare_items function.
	 */
	public function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = $this->get_items_per_page( 'automatewoo_carts_per_page' );

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

		$status = Clean::string( aw_request( 'filter_status' ) );
		if ( $status && isset( Carts::get_statuses()[ $status ] ) ) {
			// Legacy carts stored an empty status, which get_status() and the
			// factory treat as abandoned, so include them when filtering Abandoned.
			$query->where_status(
				Cart::STATUS_ABANDONED === $status ? array( Cart::STATUS_ABANDONED, '' ) : $status
			);
		}

		$res = $query->get_results();

		$this->items = $res;

		$this->max_items = $query->found_rows;
	}


	/**
	 * Retrieve the bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk_mark_active'    => __( 'Change status to active', 'automatewoo' ),
			'bulk_mark_abandoned' => __( 'Change status to abandoned', 'automatewoo' ),
			'bulk_mark_emptied'   => __( 'Change status to emptied', 'automatewoo' ),
			'bulk_mark_placed'    => __( 'Change status to placed', 'automatewoo' ),
			'bulk_mark_recovered' => __( 'Change status to recovered', 'automatewoo' ),
			'bulk_delete'         => __( 'Delete', 'automatewoo' ),
		];

		return $actions;
	}


	/**
	 * Display status filter.
	 */
	private function output_status_filter() {
		$selected_status = Clean::string( aw_request( 'filter_status' ) );
		?>

		<select name="filter_status">
			<option value=""><?php esc_html_e( 'All statuses', 'automatewoo' ); ?></option>
			<?php foreach ( Carts::get_statuses() as $status => $label ) : ?>
				<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $selected_status ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php
	}
}

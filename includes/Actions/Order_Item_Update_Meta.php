<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Action_Order_Item_Update_Meta.
 *
 * @since 4.5
 * @package AutomateWoo
 */
class Action_Order_Item_Update_Meta extends Action_Order_Update_Meta {

	/**
	 * Data required for action
	 *
	 * @var array
	 */
	public $required_data_items = [ 'order', 'order_item' ];

	/**
	 * Load admin details
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->group       = __( 'Order Item', 'automatewoo' );
		$this->description = __( 'This action can add or update an order item\'s custom field.', 'automatewoo' );
	}

	/**
	 * Run action
	 */
	public function run() {
		$order_item = $this->workflow->data_layer()->get_order_item();

		if ( ! $order_item ) {
			return;
		}

		$meta_key   = trim( $this->get_option( 'meta_key', true ) );
		$meta_value = $this->get_option( 'meta_value', true );

		// Make sure there is a meta key but a value is not required
		if ( $meta_key ) {
			$order_item->update_meta_data( $meta_key, $meta_value );
			$order_item->save();
		}
	}
}

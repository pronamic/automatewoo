<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Trigger_Order_Note_Added;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Order_Note;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderNoteAddedEachLineItem.
 *
 * @since   5.0.0
 * @package AutomateWoo\Triggers
 */
class OrderNoteAddedEachLineItem extends Trigger_Order_Note_Added {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'order', 'order_note', 'customer', 'order_item', 'product' ];

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Order Note Added - Each Line Item', 'automatewoo' );
		$this->description = __(
			'Fires when a note is added to an order for each line item in the order. This can include both private notes and notes to the customer. These notes appear on the right of the order edit screen.',
			'automatewoo'
		);
	}

	/**
	 * Handle when an order note is added.
	 *
	 * @param Order_Note $order_note
	 * @param WC_Order   $order
	 */
	protected function handle_order_note_added( Order_Note $order_note, WC_Order $order ) {
		$customer = Customer_Factory::get_by_order( $order );

		foreach ( $order->get_items() as $order_item_id => $order_item ) {
			$this->maybe_run(
				[
					'order'      => $order,
					'order_item' => $order_item,
					'order_note' => $order_note,
					'customer'   => $customer,
					'product'    => $order_item->get_product(),
				]
			);
		}
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Triggers\Utilities\HandleOrderNoteAdded;
use AutomateWoo\Triggers\Utilities\OrderGroup;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/***
 * Trigger_Order_Note_Added class.
 *
 * @since 2.2
 */
class Trigger_Order_Note_Added extends Trigger {

	use HandleOrderNoteAdded;
	use OrderGroup;

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::ORDER, DataTypes::ORDER_NOTE, DataTypes::CUSTOMER ];

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Order Note Added', 'automatewoo' );
		$this->description = __( 'Fires when any note is added to an order, can include both private notes and notes to the customer. These notes appear on the right of the order edit screen.', 'automatewoo' );
	}

	/**
	 * Load trigger fields.
	 */
	public function load_fields() {
		$contains = new Fields\Text();
		$contains->set_name( 'note_contains' );
		$contains->set_title( __( 'Note contains text', 'automatewoo' ) );
		$contains->set_description( __( 'Only trigger this workflow if the order note contains the certain text. This field is optional.', 'automatewoo' ) );

		$type = new Fields\Order_Note_Type();
		$type->set_placeholder( __( '[All]', 'automatewoo' ) );

		$this->add_field( $type );
		$this->add_field( $contains );
	}

	/**
	 * Get order types to target in the order note trigger.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	protected function get_target_order_types(): array {
		return [ 'shop_order' ];
	}

	/**
	 * Handle when an order note is added.
	 *
	 * @since 5.0.0
	 *
	 * @param Order_Note $order_note
	 * @param WC_Order   $order
	 */
	protected function handle_order_note_added( Order_Note $order_note, WC_Order $order ) {
		$this->maybe_run(
			[
				'customer'   => Customer_Factory::get_by_order( $order ),
				'order'      => $order,
				'order_note' => $order_note,
			]
		);
	}

	/**
	 * Validate a workflow.
	 *
	 * This method is also used by the subscription note added trigger.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$order_note = $workflow->data_layer()->get_order_note();

		if ( ! $order_note ) {
			return false;
		}

		$note_type     = $workflow->get_trigger_option( 'note_type' );
		$note_contains = $workflow->get_trigger_option( 'note_contains' );

		if ( $note_type ) {
			if ( $order_note->get_type() !== $note_type ) {
				return false;
			}
		}

		if ( $note_contains ) {
			if ( ! stristr( $order_note->content, $note_contains ) ) {
				return false;
			}
		}

		return true;
	}
}

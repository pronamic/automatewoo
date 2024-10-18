<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_User_Purchases_Product_Variation_With_Attribute
 */
class Trigger_User_Purchases_Product_Variation_With_Attribute extends Trigger {


	public $supplied_data_items = [ 'customer', 'order', 'order_item', 'product' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = [ 'order_status_changed', 'order_pending' ];


	function load_admin_details() {
		$this->title = __( 'Order Includes Product Variation with Specific Attribute', 'automatewoo');
		$this->group = __( 'Orders', 'automatewoo' );
		$this->description = __(
			"This trigger will look at the selected variations for each order item for the selected attribute terms. " .
			"For example if you have an attribute for 'size' and you select 'SML' and 'MED' in the Terms field then this trigger " .
			"will fire if an order is placed that contains a product in 'SML' or a product in 'MED'.",
			'automatewoo'  );
	}


	function load_fields() {
		$attribute = new Fields\Attribute();
		$attribute->set_required();

		$terms = new Fields\Attribute_Term();
		$terms->set_required();

		$order_status = new Fields\Order_Status( false );
		$order_status->set_required();
		$order_status->set_default('wc-completed');

		$this->add_field( $attribute );
		$this->add_field( $terms );
		$this->add_field( $order_status );
	}


	function register_hooks() {
		add_action( $this->get_hook_order_status_changed(), [ $this, 'status_changed' ], 10, 3 );
		// add special hook for orders that are created as pending and never paid
		add_action( 'automatewoo_order_pending', [ $this, 'order_pending' ] );
	}


	/**
	 * @param $order_id
	 */
	function order_pending( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->has_status( 'pending' ) ) {
			return; // ensure order is still pending
		}

		$this->status_changed( $order_id, '', 'pending' );
	}


	/**
	 * @param int $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	function status_changed( $order_id, $old_status, $new_status ) {

		if ( ! $this->has_workflows() )
			return;

		$order = wc_get_order( $order_id );
		$customer = Customer_Factory::get_by_order( $order );

		if ( ! $order || ! $customer ) {
			return;
		}

		Temporary_Data::set( 'order_new_status', $order_id, $new_status );

		// need to loop through every item as an order might have more than 1 product with a matching variation

		foreach ( $order->get_items() as $order_item_id => $order_item ) {
			/** @var \WC_Order_Item_Product $order_item */
			$this->maybe_run([
				'order'      => $order,
				'order_item' => $order_item,
				'customer'   => $customer,
				'product'    => $order_item->get_product(),
			]);
		}
	}


	/**
	 * @param $workflow Workflow
	 * @return bool
	 */
	function validate_workflow( $workflow ) {
		$order = $workflow->data_layer()->get_order();
		$order_item = $workflow->data_layer()->get_order_item();

		if ( ! $order || ! $order_item ) {
			return false;
		}

		$status_option = $workflow->get_trigger_option( 'order_status' );
		$new_status = Temporary_Data::get( 'order_new_status', $order->get_id() );

		if ( ! $this->validate_status_field( $status_option, $new_status ) ) {
			return false;
		}

		// Validate attribute terms
		$valid_attribute_terms = Clean::multi_select_values( $workflow->get_trigger_option( 'term' ) );

		// no selected terms
		if ( empty( $valid_attribute_terms ) )
			return false;

		// look for at least 1 valid term
		foreach ( $valid_attribute_terms as $valid_attribute_term ) {

			if ( ! strstr( $valid_attribute_term, '|' ) )
				continue;

			list( $attribute_term_id, $taxonomy ) = explode( '|', $valid_attribute_term );

			$target_term = get_term( $attribute_term_id, $taxonomy );
			$actual_term_slug = $order_item->get_meta( $taxonomy );

			if ( $actual_term_slug ) {
				if ( $actual_term_slug === $target_term->slug )
					return true;
			}
		}

		return false;
	}

}

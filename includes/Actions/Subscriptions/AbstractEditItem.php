<?php

namespace AutomateWoo\Actions\Subscriptions;

use AutomateWoo\Action;
use AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define shared methods to add, remove or update line items on a subscription.
 *
 * @since 5.4.0
 */
abstract class AbstractEditItem extends Action {


	/**
	 * A subscription is needed so that it can be edited by instances of this action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'subscription' ];


	/**
	 * Flag to define whether the quantity input field should be marked as required.
	 *
	 * @var bool
	 */
	protected $require_quantity_field = true;


	/**
	 * Method to get the item to edit on a subscription, which might be a
	 * WC_Product, WC_Coupon, or some other data type.
	 *
	 * @return mixed
	 */
	abstract protected function get_object_for_edit();


	/**
	 * Add, remove or update a line item on a subscription based on a provided object.
	 *
	 * The object to edit on a subscription can be a WC_Product, WC_Coupon, or some other WooCommerce data type.
	 *
	 * @param mixed            $object WC_Product, WC_Coupon, or some other WooCommerce data type. Will be the same data type as the return value of @see $this->get_object_for_edit().
	 * @param \WC_Subscription $subscription Instance of the subscription being edited by this action.
	 *
	 * @throws \Exception When there is an error.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	abstract protected function edit_subscription( $object, $subscription );


	/**
	 * Get the note to record on the subscription to record the line item change
	 *
	 * @param mixed $object WC_Product, WC_Coupon, or some other WooCommerce data type. Will be the same data type as the return value of @see $this->get_object_for_edit().
	 * @return string
	 */
	abstract protected function get_note( $object );


	/**
	 * Set the group for all edit actions that extend this class
	 */
	public function load_admin_details() {
		$this->group = __( 'Subscription', 'automatewoo' );
	}


	/**
	 * Edit the item managed by this class on the subscription passed in the workflow's trigger
	 *
	 * @throws \Exception When there is an error.
	 */
	public function run() {
		$object       = $this->get_object_for_edit();
		$subscription = $this->get_subscription_to_edit();

		if ( ! $object || ! $subscription ) {
			return;
		}

		$edited = $this->edit_subscription( $object, $subscription );
		if ( $edited ) {
			$this->add_note( $object, $subscription );
		}
	}


	/**
	 * Add a note to record the edit action on the subscription.
	 *
	 * @param mixed            $object WC_Product, WC_Coupon, or some other WooCommerce data type. Will be the same data type as the return value of @see $this->get_object_for_edit().
	 * @param \WC_Subscription $subscription Instance of the subscription being edited by this action.
	 */
	protected function add_note( $object, $subscription ) {
		$subscription->add_order_note( $this->get_note( $object ), false, false );
	}


	/**
	 * Get the subscription passed in by the workflow's trigger.
	 *
	 * @return \WC_Subscription|false
	 */
	protected function get_subscription_to_edit() {
		return $this->workflow->data_layer()->get_subscription();
	}


	/**
	 * Add a field to enter the product line item quantity to the action's admin input field.
	 *
	 * @param int      $min Minimum value to allow as input. Default 1.
	 * @param null|int $max Maximum value to allow as input. Default null, no maximum.
	 */
	protected function add_quantity_field( $min = 1, $max = null ) {

		$quantity_input = new Fields\Number();

		if ( null !== $max ) {
			$quantity_input->set_max( $max );
		}

		$quantity_input->set_min( $min );
		$quantity_input->set_name( 'quantity' );
		$quantity_input->set_title( __( 'Quantity', 'automatewoo' ) );
		$quantity_input->set_description( $this->get_quantity_field_description() );

		if ( $this->require_quantity_field ) {
			$quantity_input->set_required();
		}

		$this->add_field( $quantity_input );
	}


	/**
	 * Field to set a name on the line item when this action is run
	 */
	protected function add_name_field() {
		$name_field = new Fields\Text();
		$name_field->set_name( 'line_item_name' );
		$name_field->set_title( $this->get_name_field_title() );
		$name_field->set_description( $this->get_name_field_description() );
		$name_field->set_variable_validation();
		$this->add_field( $name_field );
	}


	/**
	 * Get the title to display on the name field for this action
	 */
	protected function get_name_field_title() {
		return __( 'Custom Item Name', 'automatewoo' );
	}


	/**
	 * Get the description to display on the name field for this action
	 */
	protected function get_name_field_description() {
		return __( 'The name to set on the line item.', 'automatewoo' );
	}


	/**
	 * Get the description to display on the quantity field for this action
	 */
	protected function get_quantity_field_description() {
		return '';
	}


	/**
	 * Field to set a price when this action is run
	 */
	protected function add_cost_field() {
		$cost_field = new Fields\Price();
		$cost_field->set_name( 'line_item_cost' );
		$cost_field->set_title( $this->get_cost_field_title() );
		$cost_field->set_description( $this->get_cost_field_description() );
		$cost_field->set_placeholder( __( 'E.g. 10.00', 'automatewoo' ) );
		$cost_field->set_variable_validation();
		$this->add_field( $cost_field );
	}


	/**
	 * Get the title to display on the price field for this action
	 */
	protected function get_cost_field_title() {
		/* translators: Excluding tax label (ex. tax). */
		return sprintf( __( 'Custom Item Cost %s', 'automatewoo' ), WC()->countries->ex_tax_or_vat() );
	}


	/**
	 * Get the description to display on the price field for this action
	 */
	protected function get_cost_field_description() {
		return __( 'Optionally set a custom amount, excluding tax, to use for the line item\'s cost. Do not include a currency symbol. Total line item cost will be this amount * line item\'s quantity.', 'automatewoo' );
	}

	/**
	 * Get the description to display on the price field for this action
	 *
	 * @deprecated in 5.1.0
	 *
	 * @return string
	 */
	protected function get_recalculate_coupons_compatibility_text() {
		wc_deprecated_function( __METHOD__, '5.1.0' );
		return __( 'The subscription\'s coupon discount amount will only be recalculated if you are using WooCommerce version 3.8 or higher.', 'automatewoo' );
	}

	/**
	 * Recalculate a subscription's totals.
	 *
	 * Recalculates coupons if they have been applied to the subscription.
	 *
	 * @param \WC_Subscription $subscription
	 *
	 * @since 4.8.0
	 */
	protected function recalculate_subscription_totals( $subscription ) {
		if ( ! empty( $subscription->get_coupons() ) ) {
			$subscription->recalculate_coupons();
		} else {
			$subscription->calculate_totals();
		}
	}
}

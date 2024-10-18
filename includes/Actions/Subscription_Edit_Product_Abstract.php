<?php

namespace AutomateWoo;

use AutomateWoo\Actions\Subscriptions\AbstractEditItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define shared methods to add, remove or update product line items on a subscription.
 *
 * @class Action_Subscription_Edit_Product_Abstract
 * @since 4.4
 */
abstract class Action_Subscription_Edit_Product_Abstract extends AbstractEditItem {


	/**
	 * Flag to define whether the instance of this action requires a quantity input field to
	 * be displayed on the action's admin UI.
	 *
	 * @var bool
	 */
	protected $load_quantity_field = true;


	/**
	 * Flag to define whether the instance of this action requires a name text input field.
	 *
	 * @var bool
	 */
	protected $load_name_field = false;


	/**
	 * Flag to define whether the instance of this action requires a price input field to
	 * be displayed on the action's admin UI.
	 *
	 * @var bool
	 */
	protected $load_cost_field = false;


	/**
	 * Flag to define whether variable products should be included in search results for the
	 * product select field.
	 *
	 * @var bool
	 */
	protected $allow_variable_products = true;


	/**
	 * Add a product selection field to the action's admin UI for store owners to choose what
	 * product to edit on the trigger's subscription.
	 *
	 * Optionally also add the quantity input field for the product if the instance requires it.
	 */
	public function load_fields() {
		$this->add_product_select_field();

		if ( $this->load_quantity_field ) {
			$this->add_quantity_field();
		}

		if ( $this->load_name_field ) {
			$this->add_name_field();
		}

		if ( $this->load_cost_field ) {
			$this->add_cost_field();
		}
	}


	/**
	 * Implement abstract AbstractEditItem method to get the product to
	 * edit on a subscription.
	 *
	 * @return \WC_Product|false
	 */
	protected function get_object_for_edit() {
		return wc_get_product( $this->get_option( 'product' ) );
	}


	/**
	 * Add a product selection field for this action
	 */
	protected function add_product_select_field() {
		$product_select = new Fields\Product();
		$product_select->set_required();
		$product_select->set_allow_variations( true );
		$product_select->set_allow_variable( $this->allow_variable_products );

		$this->add_field( $product_select );
	}


	/**
	 * Get the title to display on the name field for this action
	 */
	protected function get_name_field_title() {
		return __( 'Custom Product Name', 'automatewoo' );
	}


	/**
	 * Get the description to display on the name field for this action
	 */
	protected function get_name_field_description() {
		return __( 'Optionally set a custom name for the product line item added to the subscription. Defaults to the name set on the product.', 'automatewoo' );
	}


	/**
	 * Get the title to display on the price field for this action
	 */
	protected function get_cost_field_title() {
		return __( 'Custom Product Price', 'automatewoo' );
	}


	/**
	 * Get the description to display on the price field for this action
	 */
	protected function get_cost_field_description() {
		return __( 'Optionally set a custom price to use for the line item\'s cost. Do not include a currency symbol. Total line item cost will be this amount * quantity. Price should be entered the same as it would be on the Edit Product screen - taxes inclusive or exclusive. Defaults to price set on the product.', 'automatewoo' );
	}
}

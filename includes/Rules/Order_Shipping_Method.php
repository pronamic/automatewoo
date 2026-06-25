<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Shipping_Method
 */
class Order_Shipping_Method extends Preloaded_Select_Rule_Abstract {

	/** @var string */
	public $data_item = 'order';

	/** @var bool */
	public $is_multi = true;


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Shipping Method', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		$choices = [];

		foreach ( WC()->shipping()->get_shipping_methods() as $method_id => $method ) {
			$choices[ $method_id ] = $method->get_method_title();
		}

		return $choices;
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		$methods = [];

		foreach ( $order->get_shipping_methods() as $shipping_line_item ) {
			$methods[] = $shipping_line_item->get_method_id();
		}

		return $this->validate_select( $methods, $compare, $value );
	}
}

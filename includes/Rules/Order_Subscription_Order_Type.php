<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Subscription_Workflow_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Subscription_Order_Type
 *
 * @since 4.8.0
 * @package AutomateWoo\Rules
 */
class Order_Subscription_Order_Type extends Preloaded_Select_Rule_Abstract {

	/**
	 * Rule data type.
	 *
	 * @var string
	 */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Subscription Order Type', 'automatewoo' );
	}

	/**
	 * Load select choices for rule.
	 *
	 * @return array
	 */
	public function load_select_choices() {
		return Subscription_Workflow_Helper::get_subscription_order_types();
	}

	/**
	 * Validate rule.
	 *
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param array     $value
	 *
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		if ( ! $value ) {
			// value should not be blank
			return false;
		}

		$contains = wcs_order_contains_subscription( $order, $value );

		return $compare === 'is' ? $contains : ! $contains;
	}
}

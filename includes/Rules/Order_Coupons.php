<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Coupons.
 *
 * @package AutomateWoo\Rules
 */
class Order_Coupons extends Searchable_Select_Rule_Abstract {

	/**
	 * The rule's primary data item.
	 *
	 * @var string
	 */
	public $data_item = 'order';

	/**
	 * The CSS class to use on the search field.
	 *
	 * @var string
	 */
	public $class = 'wc-product-search';

	/**
	 * This rule supports multiple selections.
	 *
	 * @var bool
	 */
	public $is_multi = true;

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Coupons', 'automatewoo' );
	}

	/**
	 * Get the ajax action to use for the AJAX search.
	 *
	 * @return string
	 */
	public function get_search_ajax_action() {
		return 'aw_json_search_coupons';
	}

	/**
	 * Validate the rule for a given order.
	 *
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param array     $expected_coupons
	 *
	 * @return bool
	 */
	public function validate( $order, $compare, $expected_coupons ) {
		return $this->validate_select_case_insensitive( $order->get_coupon_codes(), $compare, $expected_coupons );
	}
}

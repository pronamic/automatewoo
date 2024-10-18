<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Logic_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Item_Product
 *
 * @since 4.9.0
 */
class Order_Item_Product extends Product_Select_Rule_Abstract {

	/**
	 * Data item the rule uses.
	 *
	 * @var string
	 */
	public $data_item = 'order_item';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title         = __( 'Order Line Item - Product', 'automatewoo' );
		$this->compare_types = $this->get_is_or_not_compare_types();
	}

	/**
	 * Validate the rule.
	 *
	 * @param \WC_Order_Item_Product $item
	 * @param string                 $compare
	 * @param string                 $expected
	 *
	 * @return bool
	 */
	public function validate( $item, $compare, $expected ) {
		$expected_product = wc_get_product( absint( $expected ) );

		if ( ! $expected_product ) {
			return false;
		}

		$matched = Logic_Helper::match_products( $item->get_product(), $expected_product );

		return $compare === 'is' ? $matched : ! $matched;
	}
}

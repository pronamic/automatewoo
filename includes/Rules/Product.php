<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Logic_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Product
 */
class Product extends Product_Select_Rule_Abstract {

	public $data_item = 'product';


	function init() {
		parent::init();

		$this->title         = __( 'Product - Product', 'automatewoo' );
		$this->compare_types = $this->get_is_or_not_compare_types();
	}


	/**
	 * @param \WC_Product|\WC_Product_Variation $product
	 * @param $compare
	 * @param $expected
	 * @return bool
	 */
	function validate( $product, $compare, $expected ) {
		$expected_product = wc_get_product( absint( $expected ) );

		if ( ! $expected_product ) {
			return false;
		}

		$match = Logic_Helper::match_products( $product, $expected_product );

		switch ( $compare ) {
			case 'is':
				return $match;
			case 'is_not':
				return ! $match;
		}
	}

}

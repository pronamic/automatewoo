<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Logic_Helper
 */
class Logic_Helper {


	/**
	 * Check if two products are the same, complexity aries when considering variations
	 *
	 * @param \WC_Product $actual_product
	 * @param \WC_Product $expected_product
	 * @return bool
	 */
	static function match_products( $actual_product, $expected_product ) {

		if ( ! $actual_product ) {
			return false;
		}

		$match = false;

		if ( $expected_product->is_type( 'variation' ) ) {
			// match a specific variation
			if ( $expected_product->get_id() == $actual_product->get_id() ) {
				$match = true;
			}
		}
		else {
			// match the main product or any of its variations
			$actual_main_product_id = $actual_product->is_type( 'variation' ) ? $actual_product->get_parent_id() : $actual_product->get_id();

			if ( $expected_product->get_id() == $actual_main_product_id ) {
				$match = true;
			}
		}

		return $match;
	}

}
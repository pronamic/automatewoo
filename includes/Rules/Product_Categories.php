<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Fields_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Product_Categories
 */
class Product_Categories extends Preloaded_Select_Rule_Abstract {

	public $data_item = 'product';

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( 'Product - Categories', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return Fields_Helper::get_categories_list();
	}


	/**
	 * @param $product \WC_Product|\WC_Product_Variation
	 * @param $compare
	 * @param $expected
	 * @return bool
	 */
	function validate( $product, $compare, $expected ) {
		if ( empty( $expected ) ) {
			return false;
		}

		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$categories = wp_get_object_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );

		return $this->validate_select( $categories, $compare, $expected );
	}
}

<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product_Select_Rule_Abstract
 *
 * @package AutomateWoo\Rules
 */
abstract class Product_Select_Rule_Abstract extends Searchable_Select_Rule_Abstract {

	/**
	 * The CSS class to use on the search field.
	 *
	 * @var string
	 */
	public $class = 'wc-product-search';

	/**
	 * Init.
	 */
	public function init() {
		parent::init();

		$this->placeholder = __( 'Search products...', 'automatewoo' );
	}

	/**
	 * Display product name on frontend.
	 *
	 * @param int $product_id
	 * @return string|int
	 */
	public function get_object_display_value( $product_id ) {
		$product_id = absint( $product_id );
		$product    = wc_get_product( $product_id );

		return $product ? $product->get_formatted_name() : $product_id;
	}

	/**
	 * Get the ajax action to use for the AJAX search.
	 *
	 * @return string
	 */
	public function get_search_ajax_action() {
		return 'woocommerce_json_search_products_and_variations';
	}
}

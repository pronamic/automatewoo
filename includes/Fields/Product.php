<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable product field class.
 *
 * @package AutomateWoo\Fields
 */
class Product extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'product';

	/**
	 * Allow product variations to be possible selections.
	 *
	 * @var bool
	 */
	public $allow_variations = false;

	/**
	 * Flag to define whether variable products should be included in search results for the select box.
	 *
	 * @var bool
	 */
	public $allow_variable = true;

	/**
	 * Product constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Product', 'automatewoo' ) );
	}

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		if ( $this->allow_variable && $this->allow_variations ) {
			return 'woocommerce_json_search_products_and_variations';
		} elseif ( false === $this->allow_variable && true === $this->allow_variations ) {
			return 'aw_json_search_products_and_variations_not_variable';
		} elseif ( false === $this->allow_variable && false === $this->allow_variations ) {
			return 'aw_json_search_products_not_variations_not_variable';
		} else {
			// allows variable but not variations
			return 'woocommerce_json_search_products';
		}
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		$product = wc_get_product( $value );

		if ( $product ) {
			return $product->get_formatted_name();
		}

		return __( '(Product not found)', 'automatewoo' );
	}

	/**
	 * Set allow_variations property.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $allow
	 *
	 * @return $this
	 */
	public function set_allow_variations( $allow ) {
		$this->allow_variations = $allow;
		return $this;
	}

	/**
	 * Set allow_variable property.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $allow
	 *
	 * @return $this
	 */
	public function set_allow_variable( $allow ) {
		$this->allow_variable = $allow;
		return $this;
	}
}

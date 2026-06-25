<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Fields_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Cart_Item_Categories
 */
class Cart_Item_Categories extends Preloaded_Select_Rule_Abstract {

	/** @var string */
	public $data_item = DataTypes::CART;

	/** @var bool */
	public $is_multi = true;


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Cart - Item Categories', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		return Fields_Helper::get_categories_list();
	}


	/**
	 * @param \AutomateWoo\Cart $cart
	 * @param string            $compare
	 * @param mixed             $expected
	 * @return bool
	 */
	public function validate( $cart, $compare, $expected ) {

		if ( empty( $expected ) ) {
			return false;
		}

		$category_ids = [];

		foreach ( $cart->get_items() as $item ) {
			$terms        = wp_get_object_terms( $item->get_product_id(), 'product_cat', [ 'fields' => 'ids' ] );
			$category_ids = array_merge( $category_ids, $terms );
		}

		$category_ids = array_filter( $category_ids );

		return $this->validate_select( $category_ids, $compare, $expected );
	}
}

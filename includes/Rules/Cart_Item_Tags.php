<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Fields_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Cart_Item_Tags
 */
class Cart_Item_Tags extends Preloaded_Select_Rule_Abstract {

	public $data_item = DataTypes::CART;

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( 'Cart - Item Tags', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return Fields_Helper::get_product_tags_list();
	}


	/**
	 * @param $cart \AutomateWoo\Cart
	 * @param $compare
	 * @param $expected
	 * @return bool
	 */
	function validate( $cart, $compare, $expected ) {
		if ( empty( $expected ) ) {
			return false;
		}

		$tag_ids = [];

		foreach ( $cart->get_items() as $item ) {
			$terms = wp_get_object_terms( $item->get_product_id(), 'product_tag', [ 'fields' => 'ids' ] );
			$tag_ids = array_merge( $tag_ids, $terms );
		}

		$tag_ids = array_filter( $tag_ids );

		return $this->validate_select( $tag_ids, $compare, $expected );
	}
}

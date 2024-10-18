<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Rule_Order_Item_Categories
 */
class Rule_Order_Item_Categories extends Rules\Preloaded_Select_Rule_Abstract {

	public $data_item = 'order';

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( 'Order - Item Categories', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return Fields_Helper::get_categories_list();
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $expected
	 * @return bool
	 */
	function validate( $order, $compare, $expected ) {

		if ( empty( $expected ) ) {
			return false;
		}

		$category_ids = [];

		foreach ( $order->get_items() as $item ) {
			$terms = wp_get_object_terms( $item->get_product_id(), 'product_cat', [ 'fields' => 'ids' ] );
			$category_ids = array_merge( $category_ids, $terms );
		}

		$category_ids = array_filter( $category_ids );

		return $this->validate_select( $category_ids, $compare, $expected );
	}
}

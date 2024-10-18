<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Fields_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Purchased_Categories
 */
class Customer_Purchased_Categories extends Preloaded_Select_Rule_Abstract {

	public $data_item = DataTypes::CUSTOMER;

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( "Customer - Purchased Categories - All Time", 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return Fields_Helper::get_categories_list();
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $expected
	 * @return bool
	 */
	function validate( $customer, $compare, $expected ) {
		if ( empty( $expected ) ) {
			return false;
		}

		$category_ids = [];

		foreach ( $customer->get_purchased_products() as $id ) {
			$terms = wp_get_object_terms( $id, 'product_cat', [ 'fields' => 'ids' ] );
			$category_ids = array_merge( $category_ids, $terms );
		}

		$category_ids = array_filter( $category_ids );

		return $this->validate_select( $category_ids, $compare, $expected );
	}
}

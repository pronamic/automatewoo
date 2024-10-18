<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Fields_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Tags
 */
class Customer_Tags extends Preloaded_Select_Rule_Abstract {

	public $data_item = DataTypes::CUSTOMER;

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( 'Customer - User Tags', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return Fields_Helper::get_user_tags_list();
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {

		if ( $customer->is_registered() ) {
			$tags = wp_get_object_terms( $customer->get_user_id(), 'user_tag', [
				'fields' => 'ids'
			]);
		}
		else {
			$tags = [];
		}

		return $this->validate_select( $tags, $compare, $value );
	}

}

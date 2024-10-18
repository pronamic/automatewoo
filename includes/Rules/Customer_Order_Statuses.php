<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Order_Statuses
 */
class Customer_Order_Statuses extends Preloaded_Select_Rule_Abstract {

	public $data_item = DataTypes::CUSTOMER;

	public $is_multi = true;


	function init() {
		parent::init();

		$this->title = __( "Customer - Current Order Statuses", 'automatewoo' );
		unset( $this->compare_types[ 'matches_all' ] );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return wc_get_order_statuses();
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {

		$orders = wc_get_orders([
			'type' => 'shop_order',
			'customer' => $customer->is_registered() ? $customer->get_user_id() : $customer->get_email(),
			'limit' => -1
		]);

		$statuses = [];
		foreach ( $orders as $order ) {
			/** @var $order \WC_Order */
			$statuses[] = 'wc-' . $order->get_status();
		}

		return $this->validate_select( $statuses, $compare, $value );
	}

}

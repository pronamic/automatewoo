<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Customer_2nd_Last_Order_Date
 *
 * @since 4.8.0
 *
 * @package AutomateWoo\Rules
 */
class Customer_2nd_Last_Order_Date extends Abstract_Date {

	/**
	 * Define the data type used by the rule.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * Customer_2nd_Last_Order_Date constructor.
	 */
	public function __construct() {
		$this->has_is_past_comparision = true;
		parent::__construct();
	}

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Customer - 2nd Last Paid Order Date', 'automatewoo' );
	}

	/**
	 * Validate rule.
	 *
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param array                 $value
	 *
	 * @return bool
	 */
	public function validate( $customer, $compare, $value = null ) {
		$order = $customer->get_nth_last_paid_order( 2 );
		$date  = $order ? $order->get_date_created() : false;

		return $this->validate_date( $compare, $value, $date );
	}
}

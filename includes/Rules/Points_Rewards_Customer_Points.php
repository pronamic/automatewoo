<?php

namespace AutomateWoo\Rules;

use WC_Points_Rewards_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Customer Points Class.
 *
 * @class Points_Rewards_Customer_Points
 */
class Points_Rewards_Customer_Points extends Abstract_Number {

	/**
	 * Customer.
	 *
	 * @var string $data_item
	 */
	public $data_item = 'customer';

	/**
	 * Supports float.
	 *
	 * @var bool $support_floats
	 */
	public $support_floats = false;

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Customer - Points', 'automatewoo' );
	}

	/**
	 * Validate method.
	 *
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param string                $value
	 *
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {

		// get points if registered or set to zero if guest
		$points = $customer->is_registered() ? WC_Points_Rewards_Manager::get_users_points( $customer->get_user_id() ) : 0;

		return $this->validate_number( $points, $compare, $value );
	}
}

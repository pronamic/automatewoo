<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Guest;
use AutomateWoo\Customer_Factory;

defined( 'ABSPATH' ) || exit;

/**
 * GuestOrderCount rule class.
 */
class GuestOrderCount extends Abstract_Number {

	/** @var string */
	public $data_item = 'guest';

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init
	 */
	public function init() {
		$this->title = __( 'Guest - Order Count', 'automatewoo' );
	}


	/**
	 * @param Guest  $guest
	 * @param string $compare
	 * @param mixed  $value
	 * @return bool
	 */
	public function validate( $guest, $compare, $value ) {
		$customer = Customer_Factory::get_by_email( $guest->get_email() );
		return $this->validate_number( $customer->get_order_count(), $compare, $value );
	}
}

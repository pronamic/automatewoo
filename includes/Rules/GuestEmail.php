<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Guest;

defined( 'ABSPATH' ) || exit;

/**
 * GuestEmail rule class.
 */
class GuestEmail extends Abstract_String {

	/** @var string */
	public $data_item = 'guest';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Guest - Email', 'automatewoo' );
	}


	/**
	 * @param Guest  $guest
	 * @param string $compare
	 * @param mixed  $value
	 * @return bool
	 */
	public function validate( $guest, $compare, $value ) {
		return $this->validate_string( $guest->get_email(), $compare, $value );
	}
}

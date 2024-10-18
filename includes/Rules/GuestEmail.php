<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Guest;

defined( 'ABSPATH' ) || exit;

/**
 * GuestEmail rule class.
 */
class GuestEmail extends Abstract_String {

	public $data_item = 'guest';


	function init() {
		$this->title = __( 'Guest - Email', 'automatewoo' );
	}


	/**
	 * @param Guest $guest
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $guest, $compare, $value ) {
		return $this->validate_string( $guest->get_email(), $compare, $value );
	}

}

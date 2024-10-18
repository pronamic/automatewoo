<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Guest;

defined( 'ABSPATH' ) || exit;

/**
 * GuestRunCount rule class.
 */
class GuestRunCount extends Abstract_Number {

	public $data_item = 'guest';

	public $support_floats = false;


	function init() {
		$this->title = __( "Workflow - Run Count For Guest", 'automatewoo' );
	}


	/**
	 * @param Guest $guest
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $guest, $compare, $value ) {

		if ( ! $workflow = $this->get_workflow() )
			return false;

		return $this->validate_number( $workflow->get_times_run_for_guest( $guest ), $compare, $value );
	}

}

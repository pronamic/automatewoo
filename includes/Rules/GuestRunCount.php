<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Guest;

defined( 'ABSPATH' ) || exit;

/**
 * GuestRunCount rule class.
 */
class GuestRunCount extends Abstract_Number {

	/** @var string */
	public $data_item = 'guest';

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Workflow - Run Count For Guest', 'automatewoo' );
	}


	/**
	 * @param Guest  $guest
	 * @param string $compare
	 * @param mixed  $value
	 * @return bool
	 */
	public function validate( $guest, $compare, $value ) {

		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false;
		}

		return $this->validate_number( $workflow->get_times_run_for_guest( $guest ), $compare, $value );
	}
}

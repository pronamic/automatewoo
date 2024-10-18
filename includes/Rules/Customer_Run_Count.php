<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Run_Count
 */
class Customer_Run_Count extends Abstract_Number {

	public $data_item = DataTypes::CUSTOMER;

	public $support_floats = false;


	function init() {
		$this->title = __( "Workflow - Run Count For Customer", 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {

		if ( ! $workflow = $this->get_workflow() )
			return false;

		return $this->validate_number( $workflow->get_run_count_for_customer( $customer ), $compare, $value );
	}

}

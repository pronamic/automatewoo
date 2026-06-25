<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Run_Count
 */
class Customer_Run_Count extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Workflow - Run Count For Customer', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {

		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false;
		}

		return $this->validate_number( $workflow->get_run_count_for_customer( $customer ), $compare, $value );
	}
}

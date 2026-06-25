<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderRunCount rule class.
 */
class OrderRunCount extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::ORDER;

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Workflow - Run Count For Order', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param string   $compare
	 * @param mixed    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false;
		}

		return $this->validate_number( $workflow->get_run_count_for_order( $order ), $compare, $value );
	}
}

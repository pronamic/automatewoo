<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderRunCount rule class.
 */
class OrderRunCount extends Abstract_Number {

	public $data_item = DataTypes::ORDER;

	public $support_floats = false;


	function init() {
		$this->title = __( "Workflow - Run Count For Order", 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		if ( ! $workflow = $this->get_workflow() ) {
			return false;
		}

		return $this->validate_number( $workflow->get_run_count_for_order( $order ), $compare, $value );
	}

}

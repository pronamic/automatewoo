<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Meta
 */
class Customer_Meta extends Abstract_Meta {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * Init the rule
	 */
	public function init() {
		$this->title = __( 'Customer - Custom Field', 'automatewoo' );
	}


	/**
	 * Validate the rule based on options set by a workflow
	 *
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param array                 $value_data
	 * @return bool
	 */
	public function validate( $customer, $compare, $value_data ) {

		$value_data = $this->prepare_value_data( $value_data );

		if ( ! is_array( $value_data ) ) {
			return false;
		}

		return $this->validate_meta( $customer->get_legacy_meta( $value_data['key'] ), $compare, $value_data['value'] );
	}
}

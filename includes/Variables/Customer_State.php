<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_State
 */
class Variable_Customer_State extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the customer's billing state.", 'automatewoo' );

		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the abbreviation or full name of the state.', 'automatewoo' ),
			[
				''             => __( 'Full', 'automatewoo' ),
				'abbreviation' => __( 'Abbreviation', 'automatewoo' ),
			],
			false
		);
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'full';

		$state   = $workflow->data_layer()->get_customer_state();
		$country = $workflow->data_layer()->get_customer_country();

		switch ( $format ) {
			case 'full':
				return aw_get_state_name( $country, $state );
			case 'abbreviation':
				return $state;
		}
	}
}

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Abstract Price Class.
 *
 * @since 4.5.0
 *
 * @class Variable_Abstract_Price
 */
abstract class Variable_Abstract_Price extends Variable {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		$this->add_parameter_select_field(
			'format',
			__( 'Choose to display the amount as a formatted price or numerical value.', 'automatewoo' ),
			[
				''        => __( 'Price', 'automatewoo' ),
				'decimal' => __( 'Decimal', 'automatewoo' ),
			],
			false
		);
	}

	/**
	 * Maybe Format Price.
	 *
	 * @param  string $amount
	 * @param  array  $parameters
	 * @param  string $currency
	 *
	 * @return string
	 */
	protected function format_amount( $amount, $parameters, $currency = null ) {

		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'price';

		switch ( $format ) {
			case 'decimal':
				return wc_format_localized_price( Format::decimal( $amount ) );
			default:
				return wc_price( $amount, [ 'currency' => $currency ] );
		}
	}
}

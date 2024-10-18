<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Price
 */
class Price extends Text {

	protected $name = 'price';

	protected $type = 'text';


	function __construct() {
		parent::__construct();

		$this->set_title( __( 'Price', 'automatewoo' ) );
		$this->classes[] = 'automatewoo-field--type-price';
	}

	/**
	 * Sanitizes the field value.
	 *
	 * Removes currency symbols, thousand separators and sets correct decimal places.
	 * Empty string values are deliberately allowed.
	 *
	 * @since 4.4.0
	 * @since 4.6.0 Adds support for workflow variables.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		$value = trim( $value );

		// preserve empty string values, don't convert to '0.00'
		if ( $value === '' ) {
			return '';
		}

		if ( false === strpos( $value, '{{' ) ) {
			// IMPORTANT - Must clean the price value to convert from a localized format
			return Clean::localized_price( $value );
		} else {
			return Clean::string( $value );
		}
	}

	/**
	 * Output the field HTML.
	 *
	 * @param string $value
	 */
	public function render( $value ) {
		// If not a variable localize the price value
		if ( false === strpos( $value, '{{' ) ) {
			$value = wc_format_localized_price( $value );
		}
		parent::render( $value );
	}
}

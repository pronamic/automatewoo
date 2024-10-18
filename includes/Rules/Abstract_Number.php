<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Clean;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Number
 */
abstract class Abstract_Number extends Rule {

	public $type = 'number';

	/**
	 * Set whether the rule supports floats or only integers.
	 *
	 * @var bool
	 */
	public $support_floats = true;


	function __construct() {

		if ( $this->support_floats ) {
			$this->compare_types = $this->get_float_compare_types();
		}
		else {
			$this->compare_types = $this->get_integer_compare_types();
		}

		parent::__construct();
	}

	/**
	 * Sanitizes the field value.
	 *
	 * Removes currency symbols, thousand separators and sets correct decimal places.
	 *
	 * @since 4.6.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		// Localize price even if decimal/float values are not supported so thousand separators are removed
		return Clean::localized_price( (string) $value, $this->support_floats ? null : 0 );
	}

	/**
	 * Formats a rule's value for display in the rules UI.
	 *
	 * @since 4.6.0
	 *
	 * @param string|int $value
	 *
	 * @return string
	 */
	public function format_value( $value ) {
		if ( $this->support_floats ) {
			return wc_format_localized_price( $value );
		} else {
			return strval( (int) $value );
		}
	}

}

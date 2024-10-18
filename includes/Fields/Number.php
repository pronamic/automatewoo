<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Number
 */
class Number extends Text {

	protected $name = 'number_input';

	protected $type = 'number';


	function __construct() {
		parent::__construct();
		$this->title = __( 'Number', 'automatewoo' );
	}


	/**
	 * @param $min string
	 * @return $this
	 */
	function set_min( $min ) {
		$this->add_extra_attr( 'min', $min );
		return $this;
	}


	/**
	 * @param $max string
	 * @return $this
	 */
	function set_max( $max ) {
		$this->add_extra_attr( 'max', $max );
		return $this;
	}

	/**
	 * Sanitizes the value of a number field.
	 *
	 * If the field is not required, the field can be left blank.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value
	 *
	 * @return string|float
	 */
	function sanitize_value( $value ) {
		$value = trim( $value );

		if ( ! $this->get_required() ) {
			// preserve empty string values, don't cast to float
			if ( $value === '' ) {
				return '';
			}
		}

		return (float) $value;
	}

}

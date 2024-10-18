<?php

namespace AutomateWoo\Formatters;

trait Boolean_Formatter {

	/**
	 * Format a value for display in the UI.
	 *
	 * @param mixed $value The value to format.
	 *
	 * @return bool The formatted value.
	 */
	public function format_value( $value ) {
		return (bool) $value;
	}
}

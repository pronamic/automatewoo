<?php

namespace AutomateWoo\Formatters;

interface Formattable {

	/**
	 * Format a value for display in the UI.
	 *
	 * @param mixed $value The value to format.
	 *
	 * @return mixed The formatted value.
	 */
	public function format_value( $value );
}

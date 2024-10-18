<?php

namespace AutomateWoo\Formatters;

trait Int_String_Formatter {

	/**
	 * Format a value for display in the UI.
	 *
	 * @since 4.8.0
	 *
	 * @param string|int $value
	 *
	 * @return string
	 */
	public function format_value( $value ) {
		return strval( (int) $value );
	}
}

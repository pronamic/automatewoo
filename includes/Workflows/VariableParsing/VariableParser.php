<?php

namespace AutomateWoo\Workflows\VariableParsing;

use AutomateWoo\Clean;

/**
 * Class to parse a variable string into separate usable parts.
 *
 * @since 3.6
 * @since 5.4.0 renamed from \AutomateWoo\Workflow_Variable_Parser
 */
class VariableParser {

	/**
	 * Sanitize and parse a variable string into a usable object.
	 *
	 * @param string $variable_string The part between the double curly braces.
	 *                                e.g. "customer.generate_coupon template: 'abandoned-cart'"
	 *
	 * @return ExcludedParsedVariable|ParsedVariable
	 *
	 * @throws \Exception If parsing fails.
	 */
	public function parse( string $variable_string ) {
		$variable_string = $this->sanitize( $variable_string );

		if ( $this->is_excluded( $variable_string ) ) {
			return new ExcludedParsedVariable( $variable_string );
		}

		$matches    = [];
		$parameters = [];

		// extract the variable name (first part) of the variable string, e.g. 'customer.email'
		preg_match( '/([a-z._0-9])+/', $variable_string, $matches, PREG_OFFSET_CAPTURE );

		if ( ! $matches ) {
			throw new \Exception();
		}

		$name = $matches[0][0];

		// the name must contain a period
		if ( ! strstr( $name, '.' ) ) {
			throw new \Exception();
		}

		list( $type, $field ) = explode( '.', $name, 2 );

		$parameter_string = trim( substr( $variable_string, $matches[1][1] + 1 ) );
		$parameter_string = trim( aw_str_replace_first_match( $parameter_string, '|' ) ); // remove pipe

		$parameters_split = preg_split( '/(,)(?=(?:[^\']|\'[^\']*\')*$)/', $parameter_string );

		foreach ( $parameters_split as $parameter ) {
			if ( ! strstr( $parameter, ':' ) ) {
				continue;
			}

			list( $key, $value ) = explode( ':', $parameter, 2 );

			$key   = sanitize_key( $key );
			$value = $this->unquote( $value );

			$parameters[ $key ] = $value;
		}

		return new ParsedVariable( $name, $type, $field, $parameters );
	}

	/**
	 * Sanitize the string contents of a variable (the part between the double curly braces).
	 *
	 * @param string $str
	 * @return string
	 */
	protected function sanitize( string $str ): string {
		// Remove HTML tags and breaks
		$str = wp_strip_all_tags( $str, true );

		// remove unicode white spaces
		$str = preg_replace( "#\x{00a0}#siu", ' ', $str );

		$str = trim( $str );

		return $str;
	}

	/**
	 * Remove single quotes from start and end of a string
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	private function unquote( string $str ): string {
		return trim( trim( $str ), "'" );
	}

	/**
	 * Certain variables can be excluded from processing.
	 * Currently only {{ unsubscribe_url }}.
	 *
	 * @param string $variable
	 *
	 * @return bool
	 */
	protected function is_excluded( string $variable ) {
		$excluded = apply_filters(
			'automatewoo/variables_processor/excluded',
			[
				'unsubscribe_url',
			]
		);

		return in_array( $variable, $excluded, true );
	}
}

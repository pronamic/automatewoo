<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;

/**
 * Class AbstractOptionsStore.
 *
 * @since 5.1.0
 */
abstract class AbstractOptionsStore {

	/**
	 * Get the prefix for options used the wp_options table.
	 *
	 * @return string
	 */
	abstract public function get_prefix(): string;

	/**
	 * Get an array of option defaults.
	 *
	 * @return array
	 */
	abstract public function get_defaults(): array;

	/**
	 * Get bool WP option.
	 *
	 * Booleans are stored as 'yes' 'no' values in the database.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 *
	 * @throws InvalidArgument If the option value is invalid.
	 */
	protected function get_bool_option( string $option_name ): bool {
		switch ( $this->get_option( $option_name ) ) {
			case 'yes':
				return true;
			case 'no':
				return false;
			default:
				throw InvalidArgument::invalid_argument( 'yes or no' );
		}
	}

	/**
	 * Get the value of an option or fallback to the default.
	 *
	 * @param string $option_name
	 *
	 * @return mixed The value of the option.
	 *               Returns null if there is no default and the option doesn't exist or is an empty string.
	 */
	protected function get_option( string $option_name ) {
		$default = $this->get_defaults()[ $option_name ] ?? null;
		$value   = get_option( $this->get_prefix() . $option_name, $default );

		// If the value is an empty string fall back to the default
		return ( '' === $value ) ? $default : $value;
	}
}

<?php

namespace AutomateWoo;

/**
 * @class Options_Abstract
 * @since 4.0
 */
abstract class Options_Abstract {

	/** @var string */
	public static $prefix;

	/** @var array */
	public static $defaults = [];


	/**
	 * @param string $option_name
	 * @return mixed
	 */
	public static function get( $option_name ) {
		$value = get_option( static::$prefix . $option_name );

		if ( $value !== false && $value !== '' ) {
			return static::parse( $value );
		}

		// fallback to default
		if ( isset( static::$defaults[ $option_name ] ) ) {
			return static::parse( static::$defaults[ $option_name ] );
		}

		return false;
	}


	/**
	 * Convert yes / no strings to boolean
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function parse( $value ) {
		if ( $value === 'yes' ) {
			return true;
		}
		if ( $value === 'no' ) {
			return false;
		}
		return $value;
	}
}

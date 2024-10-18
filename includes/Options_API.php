<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * WP option wrapper
 *
 * @class AW_Abstract_Options_API
 * @since 2.4.4
 *
 * @property $version
 */
abstract class Options_API {

	/** @var string */
	public $prefix;

	/** @var array */
	public $defaults = [];


	/**
	 * Magic method for option retrieval
	 *
	 * @param string $key
	 * @return mixed
	 */
	function __get( $key ) {

		$value = get_option( $this->prefix . $key );

		// ability to filter option values
		$filter_func = 'filter_' . $key;

		if ( method_exists( $this, $filter_func ) ) {
			$value = call_user_func( [ $this, $filter_func ], $value );
		}

		if ( $value !== false && $value !== '' ) {
			return $this->parse( $value );
		}

		// fallback to default
		if ( isset( $this->defaults[$key] ) ) {
			return $this->parse( $this->defaults[$key] );
		}

		return false;
	}


	/**
	 * @since 4.0
	 * @param $option_name
	 * @return mixed
	 */
	static function get( $option_name ) {
		return AW()->options()->__get( $option_name );
	}


	/**
	 * Convert yes / no strings to boolean
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	function parse( $value ) {
		if ( $value === 'yes' ) return true;
		if ( $value === 'no' ) return false;

		return $value;
	}
}


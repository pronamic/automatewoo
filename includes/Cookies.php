<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Cookies
 * @since 4.0
 */
class Cookies {


	/**
	 * Sets a cookie and also updates the $_COOKIE array.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int    $expire timestamp
	 *
	 * @return bool
	 */
	static function set( $name, $value, $expire = 0 ) {
		wc_setcookie( $name, $value, $expire, is_ssl() );
		$_COOKIE[ $name ] = $value;
		return true;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	static function get( $name ) {
		return isset( $_COOKIE[ $name ] ) ? Clean::string( $_COOKIE[ $name ] ) : false;
	}


	/**
	 * Clear a cookie and also updates the $_COOKIE array.
	 * @param $name
	 */
	static function clear( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			wc_setcookie( $name, '', time() - HOUR_IN_SECONDS, is_ssl() );
			unset( $_COOKIE[ $name ] );
		}
	}

}

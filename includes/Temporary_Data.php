<?php

namespace AutomateWoo;

/**
 * @class Temporary_Data
 * @since 2.9
 */
class Temporary_Data {

	/** @var array  */
	public static $data = [];


	/**
	 * @param string $type
	 * @param string $key
	 * @param mixed  $value
	 */
	public static function set( $type, $key, $value ) {
		self::setup_type( $type );
		self::$data[ $type ][ (string) $key ] = $value;
	}


	/**
	 * @param string $type
	 * @param string $key
	 */
	public static function delete( $type, $key ) {
		self::setup_type( $type );
		unset( self::$data[ $type ][ (string) $key ] );
	}


	/**
	 * @param string $type
	 * @param string $key
	 * @return bool
	 */
	public static function exists( $type, $key ) {
		self::setup_type( $type );
		return isset( self::$data[ $type ][ (string) $key ] );
	}


	/**
	 * @param string $type
	 * @param string $key
	 * @return mixed
	 */
	public static function get( $type, $key ) {
		self::setup_type( $type );

		if ( isset( self::$data[ $type ][ (string) $key ] ) ) {
			return self::$data[ $type ][ (string) $key ];
		}

		return false;
	}


	/**
	 * @param string $type
	 */
	public static function setup_type( $type ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			self::$data[ $type ] = [];
		}
	}


	/**
	 * Remove all data and reset
	 */
	public static function reset() {
		self::$data = [];
	}
}

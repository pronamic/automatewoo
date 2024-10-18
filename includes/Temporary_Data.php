<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Temporary_Data
 * @since 2.9
 */
class Temporary_Data {

	/** @var array  */
	static $data = [];


	/**
	 * @param string $type
	 * @param $key
	 * @param mixed $value
	 */
	static function set( $type, $key, $value ) {
		self::setup_type( $type );
		self::$data[ $type ][ (string) $key ] = $value;
	}


	/**
	 * @param $type
	 * @param $key
	 */
	static function delete( $type, $key ) {
		self::setup_type( $type );
		unset( self::$data[ $type ][ (string) $key ] );
	}


	/**
	 * @param string $type
	 * @param $key
	 * @return bool
	 */
	static function exists( $type, $key ) {
		self::setup_type( $type );
		return isset( self::$data[ $type ][ (string) $key ] );
	}


	/**
	 * @param string $type
	 * @param $key
	 * @return mixed
	 */
	static function get( $type, $key ) {
		self::setup_type( $type );

		if ( isset( self::$data[ $type ][ (string) $key ] ) ) {
			return self::$data[ $type ][ (string) $key ];
		}

		return false;
	}


	/**
	 * @param $type
	 */
	static function setup_type( $type ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			self::$data[ $type ] = [];
		}
	}


	/**
	 * Remove all data and reset
	 */
	static function reset() {
		self::$data = [];
	}

}

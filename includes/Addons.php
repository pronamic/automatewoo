<?php

namespace AutomateWoo;

/**
 * @class Addons
 */
class Addons {

	/** @var array */
	private static $registered_addons = [];


	/**
	 * @param Addon $addon
	 */
	public static function register( $addon ) {
		self::$registered_addons[ $addon->id ] = $addon;
	}


	/**
	 * @return Addon[]
	 */
	public static function get_all() {
		return self::$registered_addons;
	}


	/**
	 * @param string $id
	 * @return Addon|false
	 */
	public static function get( $id ) {
		if ( ! isset( self::$registered_addons[ $id ] ) ) {
			return false;
		}

		return self::$registered_addons[ $id ];
	}


	/**
	 * @return bool
	 */
	public static function has_addons() {
		return ! empty( self::$registered_addons );
	}
}

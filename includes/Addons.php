<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Addons
 */
class Addons {

	/** @var array */
	private static $registered_addons = [];


	/**
	 * @param $addon Addon
	 */
	static function register( $addon ) {
		self::$registered_addons[$addon->id] = $addon;
	}


	/**
	 * @return Addon[]
	 */
	static function get_all() {
		return self::$registered_addons;
	}


	/**
	 * @param $id string
	 * @return Addon|false
	 */
	static function get( $id ) {
		if ( ! isset( self::$registered_addons[$id] ) )
			return false;

		return self::$registered_addons[$id];
	}


	/**
	 * @return bool
	 */
	static function has_addons() {
		return ! empty( self::$registered_addons );
	}

}

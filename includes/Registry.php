<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Registry\ItemConstructorArgsTrait;

/**
 * @class Registry
 * @since 3.2.4
 */
abstract class Registry {

	use ItemConstructorArgsTrait;

	/** @var array - must be declared in child class */
	protected static $includes;

	/** @var array - must be declared in child class */
	protected static $loaded = [];


	/**
	 * Implement this method in sub classes
	 * @return array
	 */
	static function load_includes() {
		return [];
	}


	/**
	 * Runs after a valid item is loaded.
	 *
	 * Optionally, override this method.
	 *
	 * @param string $name
	 * @param mixed  $object
	 */
	public static function after_loaded( $name, $object ) {}


	/**
	 * @return array
	 */
	static function get_includes() {
		if ( ! isset( static::$includes ) ) {
			static::$includes = static::load_includes();
		}
		return static::$includes;
	}


	/**
	 * @return mixed
	 */
	static function get_all() {
		foreach ( static::get_includes() as $name => $path ) {
			static::load( $name );
		}
		return array_filter( static::$loaded );
	}


	/**
	 * @param $name
	 * @return bool|object
	 */
	static function get( $name ) {
		if ( static::load( $name ) ) {
			return static::$loaded[ $name ];
		}
		return false;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	static function is_loaded( $name ) {
		return isset( static::$loaded[ $name ] );
	}


	/**
	 * Load an object by name.
	 *
	 * Returns true if the object has been loaded.
	 *
	 * @since 4.9.0 Supports adding an objects directly as an include.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	static function load( $name ) {
		if ( self::is_loaded( $name ) ) {
			return true;
		}

		$includes = static::get_includes();
		$object   = false;

		if ( empty( $includes[ $name ] ) ) {
			return false;
		}

		$include = $includes[ $name ];

		if ( is_object( $include ) ) {
			// The include is already an object
			// Allows objects to be dynamically registered, useful if they have variable data
			$object = $include;
		} else {
			// Check if include is a file path or a class name
			// NOTE: the file include method should NOT be used! It is kept for compatibility.
			if ( strstr( $include, '.php' ) ) {
				if ( file_exists( $include ) ) {
					$object = include_once $include;
				}
			} else {
				// If include is not a file path, assume it's a class name
				if ( class_exists( $include ) ) {
					$object = new $include( ...static::get_item_constructor_args( $name ) );
				}
			}
		}

		if ( static::is_item_valid( $object ) ) {
			static::after_loaded( $name, $object );
			static::$loaded[ $name ] = $object;
			return true;
		} else {
			// Prevent trying to load it again
			static::$loaded[ $name ] = false;
			return false;
		}
	}

	/**
	 * Checks that an item is valid.
	 *
	 * Invalid items are prevented from being returned from the registry.
	 * This method should be overridden in child classes.
	 *
	 * @param mixed $item
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_item_valid( $item ) {
		return is_object( $item );
	}

	/**
	 * Clear all registry cached data.
	 *
	 * @since 4.4.0
	 */
	static function reset() {
		static::$includes = null;
		static::$loaded = [];
	}

}

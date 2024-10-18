<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Factory
 * @since 2.9
 */
abstract class Factory {

	/** @var array - must NOT be declared in child class  */
	static $cache = [];

	/** @var string - must be declared in child class */
	static $model;


	/**
	 * Fetches the object type from factories array
	 * @return string
	 */
	static function get_object_type() {

		$class = get_called_class();
		$factories = array_flip( Factories::get_factories() );

		if ( ! isset( $factories[ $class ] ) ) {
			return false;
		}

		return $factories[ $class ];
	}


	/**
	 * @param integer $object_id
	 * @return Model|bool|mixed
	 */
	static function get( $object_id ) {
		$object_id = Clean::id( $object_id );

		if ( ! $object_id ) {
			return false;
		}

		if ( static::is_cached( $object_id ) ) {
			return static::get_cached( $object_id );
		}

		/** @var Model $object */
		$object = new static::$model( $object_id );

		if ( ! $object || ! $object->exists ) {
			static::cache_nonexistent_object( $object_id );
			return false;
		}

		return $object;
	}


	/**
	 * @deprecated
	 * @param $object
	 * @return Model|bool
	 */
	static function load( $object ) {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		return $object;
	}



	/**
	 * Setup cache array for type
	 */
	static function setup_cache() {
		if ( ! isset( self::$cache[ static::get_object_type() ] ) )  {
			self::$cache[ static::get_object_type() ] = [];
		}
	}


	/**
	 * Does the object existing the cache, returns true if the object is false in the cache
	 * @param $object_id
	 * @return bool
	 */
	static function is_cached( $object_id ) {
		static::setup_cache();
		return isset( self::$cache[ static::get_object_type() ][ $object_id ] );
	}


	/**
	 * @param $object_id
	 * @return bool|Model
	 */
	static function get_cached( $object_id ) {
		static::setup_cache();

		if ( ! isset( self::$cache[ static::get_object_type() ][$object_id] ) ) {
			return false;
		}

		return static::$cache[ static::get_object_type() ][ $object_id ];
	}


	/**
	 * Cache the fact that the object does not exist
	 * @param $object_id
	 */
	static function cache_nonexistent_object( $object_id ) {
		static::setup_cache();
		self::$cache[ static::get_object_type() ][ $object_id ] = false;
	}


	/**
	 * @param Model $object
	 */
	static function update_cache( $object ) {
		static::setup_cache();

		if ( ! is_a( $object, 'AutomateWoo\Model' ) ) {
			return;
		}

		static::$cache[ static::get_object_type() ][ $object->get_id() ] = $object;
	}


	/**
	 * @param Model $object
	 */
	static function clean_cache( $object ) {
		static::setup_cache();

		if ( ! is_a( $object, 'AutomateWoo\Model' ) ) {
			return;
		}

		if ( isset( self::$cache[ static::get_object_type() ][ $object->get_id() ] ) ) {
			unset( self::$cache[ static::get_object_type() ][ $object->get_id() ] );
		}

	}


	/**
	 * Clears cache property for object based on new and existing values
	 *
	 * @since 3.4.2
	 * @param Model $object
	 * @param string $prop
	 * @param string $group
	 */
	static function clear_cached_prop( $object, $prop, $group ) {
		if ( isset( $object->original_data[$prop] ) ) {
			Cache::delete( $object->original_data[$prop], $group ); // clear old value, important if the value has changed
		}

		if ( isset( $object->data[$prop] ) ) {
			Cache::delete( $object->data[$prop], $group ); // clear for new value, important for newly created carts for example
		}
	}

}

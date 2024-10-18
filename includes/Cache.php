<?php

namespace AutomateWoo;

/**
 * Cache class.
 *
 * Wrapper class for WP transients and object cache.
 *
 * @since 2.1.0
 */
class Cache {

	/**
	 * Is cache enabled?
	 *
	 * @var bool
	 */
	public static $enabled = true;

	/**
	 * Get default transient expiration value in hours.
	 *
	 * @return int
	 */
	public static function get_default_transient_expiration() {
		return apply_filters( 'automatewoo_cache_default_expiration', 6 );
	}

	/**
	 * Set a transient value.
	 *
	 * @param string   $key
	 * @param mixed    $value
	 * @param bool|int $expiration In hours. Optional.
	 *
	 * @return bool
	 */
	public static function set_transient( $key, $value, $expiration = false ) {
		if ( ! self::$enabled ) {
			return false;
		}
		if ( ! $expiration ) {
			$expiration = self::get_default_transient_expiration();
		}
		return set_transient( 'aw_cache_' . $key, $value, $expiration * HOUR_IN_SECONDS );
	}

	/**
	 * Get the value of a transient.
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public static function get_transient( $key ) {
		if ( ! self::$enabled ) {
			return false;
		}
		return get_transient( 'aw_cache_' . $key );
	}

	/**
	 * Delete a transient.
	 *
	 * @param string $key
	 */
	public static function delete_transient( $key ) {
		delete_transient( 'aw_cache_' . $key );
	}

	/**
	 * Sets a value in cache.
	 *
	 * Only sets if key is not falsy.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param string $group
	 */
	public static function set( $key, $value, $group ) {
		if ( ! $key || ! self::$enabled ) {
			return;
		}
		wp_cache_set( (string) $key, $value, "automatewoo_$group" );
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return bool|mixed
	 */
	public static function get( $key, $group ) {
		if ( ! $key || ! self::$enabled ) {
			return false;
		}
		return wp_cache_get( (string) $key, "automatewoo_$group" );
	}

	/**
	 * Checks if a cache key and group value exists.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function exists( $key, $group ) {
		if ( ! $key || ! self::$enabled ) {
			return false;
		}
		$found = false;
		wp_cache_get( (string) $key, "automatewoo_$group", false, $found );
		return $found;
	}

	/**
	 * Remove the item from the cache.
	 *
	 * @param string $key
	 * @param string $group
	 */
	public static function delete( $key, $group ) {
		if ( ! $key || ! self::$enabled ) {
			return;
		}
		wp_cache_delete( (string) $key, "automatewoo_$group" );
	}
}

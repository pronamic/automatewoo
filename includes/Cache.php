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
		wp_cache_set( self::prefix_key( $key, $group ), $value, "automatewoo_{$group}" );
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
		return wp_cache_get( self::prefix_key( $key, $group ), "automatewoo_{$group}" );
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
		wp_cache_get( self::prefix_key( $key, $group ), "automatewoo_{$group}", false, $found );
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
		wp_cache_delete( self::prefix_key( $key, $group ), "automatewoo_{$group}" );
	}

	/**
	 * Flush a cache group.
	 *
	 * @param string $group Group of cache to flush.
	 *
	 * @since 6.1.6
	 */
	public static function flush_group( string $group ) {

		// Use fallback if flush group is not supported.
		if ( ! self::cache_supports_flush_group() ) {
			self::invalidate_cache_group( "automatewoo_{$group}" );
			return;
		}

		wp_cache_flush_group( "automatewoo_{$group}" );
	}

	/**
	 * Checks if the cache solution supports flush_group.
	 * Result is filtered to allow overriding this value.
	 *
	 * @return boolean
	 */
	private static function cache_supports_flush_group(): bool {
		$supports_flush_group = function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' );
		return (bool) apply_filters( 'automatewoo_cache_supports_flush_group', $supports_flush_group );
	}

	/**
	 * Get prefix for use with wp_cache_ functions. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get (already prefixed with `automatewoo_`).
	 * @return string Prefix.
	 */
	private static function get_cache_prefix( string $group ): string {
		// Get cache key - uses cache key aw_<group>_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( "{$group}_cache_prefix", $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( "{$group}_cache_prefix", $prefix, $group );
		}

		return "automatewoo_{$prefix}_";
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear (already prefixed with `automatewoo_`).
	 * @return bool
	 */
	private static function invalidate_cache_group( string $group ): bool {
		return wp_cache_set( "{$group}_cache_prefix", microtime(), $group );
	}

	/**
	 * Return a prefixed key.
	 * If flush group is not supported by the cache implementation, it returns a unique prefix.
	 *
	 * @param string $key   Cache key to prefix.
	 * @param string $group Cache group to use for unique prefix.
	 *
	 * @return string
	 */
	private static function prefix_key( $key, string $group ): string {
		if ( ! self::cache_supports_flush_group() ) {
			return self::get_cache_prefix( "automatewoo_{$group}" ) . (string) $key;
		}

		return 'automatewoo_' . (string) $key;
	}
}

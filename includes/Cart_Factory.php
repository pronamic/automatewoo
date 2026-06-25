<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Cart_Factory
 * @since 2.9
 */
class Cart_Factory extends Factory {

	/**
	 * @var string
	 */
	public static $model = 'AutomateWoo\Cart';


	/**
	 * @param int $cart_id
	 * @return Cart|bool
	 */
	public static function get( $cart_id ) {
		return parent::get( $cart_id );
	}


	/**
	 * @param int $guest_id
	 * @return Cart|bool
	 */
	public static function get_by_guest_id( $guest_id ) {

		return static::get_current_by_customer_column( 'guest_id', $guest_id, 'cart_guest_id' );
	}


	/**
	 * @param int $user_id
	 * @return Cart|bool
	 */
	public static function get_by_user_id( $user_id ) {

		return static::get_current_by_customer_column( 'user_id', $user_id, 'cart_user_id' );
	}


	/**
	 * Get all carts for a guest, including terminal carts.
	 *
	 * @param int $guest_id
	 * @return Cart[]
	 */
	public static function get_all_by_guest_id( $guest_id ) {

		return static::get_all_by_customer_column( 'guest_id', $guest_id );
	}


	/**
	 * Get all carts for a user, including terminal carts.
	 *
	 * @param int $user_id
	 * @return Cart[]
	 */
	public static function get_all_by_user_id( $user_id ) {

		return static::get_all_by_customer_column( 'user_id', $user_id );
	}


	/**
	 * @param string $token
	 * @return Cart|bool
	 */
	public static function get_by_token( $token ) {

		if ( ! $token ) {
			return false;
		}

		$cart = new Cart();
		$cart->get_by( 'token', $token );

		if ( ! $cart->exists ) {
			return false;
		}

		return $cart;
	}


	/**
	 * Get the newest active or abandoned cart for a customer identifier.
	 *
	 * @param string $column      The customer column to query.
	 * @param int    $value       The customer ID value.
	 * @param string $cache_group The cache group for this customer column.
	 *
	 * @return Cart|bool
	 */
	private static function get_current_by_customer_column( $column, $value, $cache_group ) {
		$value = Clean::id( $value );

		if ( ! $value ) {
			return false;
		}

		if ( Cache::exists( $value, $cache_group ) ) {
			$cached_cart_id = Cache::get( $value, $cache_group );

			if ( ! $cached_cart_id ) {
				return false;
			}

			$cart = static::get( $cached_cart_id );

			if ( $cart && $cart->is_current() ) {
				return $cart;
			}

			Cache::delete( $value, $cache_group );
		}

		$query = new Cart_Query();
		$query->where( $column, $value );
		// Include the legacy empty status, which Cart::get_status() reports as
		// abandoned: such carts are current in PHP, so the lookup must find them
		// too, otherwise a duplicate stored cart could be created for the customer.
		$query->where_status( array_merge( Cart::get_current_statuses(), [ '' ] ) );
		$query->set_ordering( 'id', 'DESC' );
		$query->set_limit( 1 );

		$carts = $query->get_results();

		if ( empty( $carts ) ) {
			Cache::set( $value, 0, $cache_group );
			return false;
		}

		$cart = reset( $carts );

		Cache::set( $value, $cart->get_id(), $cache_group );

		return $cart;
	}


	/**
	 * Get all carts for a customer identifier, including terminal carts.
	 *
	 * @param string $column The customer column to query.
	 * @param int    $value  The customer ID value.
	 *
	 * @return Cart[]
	 */
	private static function get_all_by_customer_column( $column, $value ) {
		$value = Clean::id( $value );

		if ( ! $value ) {
			return [];
		}

		$query = new Cart_Query();
		$query->where( $column, $value );
		$query->set_ordering( 'id', 'DESC' );

		return $query->get_results();
	}


	/**
	 * @param Cart $cart
	 */
	public static function update_cache( $cart ) {
		parent::update_cache( $cart );

		if ( $cart->get_guest_id() ) {
			if ( $cart->is_current() ) {
				Cache::set( $cart->get_guest_id(), $cart->get_id(), 'cart_guest_id' );
			} else {
				Cache::delete( $cart->get_guest_id(), 'cart_guest_id' );
			}
		}

		if ( $cart->get_user_id() ) {
			if ( $cart->is_current() ) {
				Cache::set( $cart->get_user_id(), $cart->get_id(), 'cart_user_id' );
			} else {
				Cache::delete( $cart->get_user_id(), 'cart_user_id' );
			}
		}
	}


	/**
	 * @param Cart $cart
	 */
	public static function clean_cache( $cart ) {
		parent::clean_cache( $cart );

		static::clear_cached_prop( $cart, 'guest_id', 'cart_guest_id' );
		static::clear_cached_prop( $cart, 'user_id', 'cart_user_id' );
	}
}

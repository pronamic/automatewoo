<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Cart_Factory
 * @since 2.9
 */
class Cart_Factory extends Factory {

	static $model = 'AutomateWoo\Cart';


	/**
	 * @param int $cart_id
	 * @return Cart|bool
	 */
	static function get( $cart_id ) {
		return parent::get( $cart_id );
	}


	/**
	 * @param $guest_id
	 * @return Cart|bool
	 */
	static function get_by_guest_id( $guest_id ) {

		if ( ! $guest_id ) return false;

		if ( Cache::exists( $guest_id, 'cart_guest_id' ) ) {
			return static::get( Cache::get( $guest_id, 'cart_guest_id' ) );
		}

		$cart = new Cart();
		$cart->get_by( 'guest_id', $guest_id );

		if ( ! $cart->exists ) {
			Cache::set( $guest_id, 0, 'cart_guest_id' );
			return false;
		}

		return $cart;
	}


	/**
	 * @param $user_id
	 * @return Cart|bool
	 */
	static function get_by_user_id( $user_id ) {

		if ( ! $user_id ) return false;

		if ( Cache::exists( $user_id, 'cart_user_id' ) ) {
			return static::get( Cache::get( $user_id, 'cart_user_id' ) );
		}

		$cart = new Cart();
		$cart->get_by( 'user_id', $user_id );

		if ( ! $cart->exists ) {
			Cache::set( $user_id, 0, 'cart_user_id' );
			return false;
		}

		return $cart;
	}


	/**
	 * @param $token
	 * @return Cart|bool
	 */
	static function get_by_token( $token ) {

		if ( ! $token ) return false;

		$cart = new Cart();
		$cart->get_by( 'token', $token );

		if ( ! $cart->exists ) {
			return false;
		}

		return $cart;
	}


	/**
	 * @param Cart $cart
	 */
	static function update_cache( $cart ) {
		parent::update_cache( $cart );

		if ( $cart->get_guest_id() ) {
			Cache::set( $cart->get_guest_id(), $cart->get_id(), 'cart_guest_id' );
		}

		if ( $cart->get_user_id() ) {
			Cache::set( $cart->get_user_id(), $cart->get_id(), 'cart_user_id' );
		}
	}


	/**
	 * @param Cart $cart
	 */
	static function clean_cache( $cart ) {
		parent::clean_cache( $cart );

		static::clear_cached_prop( $cart, 'guest_id', 'cart_guest_id' );
		static::clear_cached_prop( $cart, 'user_id', 'cart_user_id' );
	}

}
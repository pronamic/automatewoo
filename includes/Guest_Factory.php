<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guest_Factory
 * @since 2.9
 */
class Guest_Factory extends Factory {

	static $model = 'AutomateWoo\Guest';


	/**
	 * @param int $guest_id
	 * @return Guest|bool
	 */
	static function get( $guest_id ) {
		return parent::get( $guest_id );
	}


	/**
	 * @param $email
	 * @return Guest|bool
	 */
	static function get_by_email( $email ) {

		if ( ! is_email( $email ) ) return false;

		if ( Cache::exists( $email, 'guest_email' ) ) {
			return static::get( Cache::get( $email, 'guest_email' ) );
		}

		$guest = new Guest();
		$guest->get_by( 'email', $email );

		if ( ! $guest->exists ) {
			Cache::set( $email, 0, 'guest_email' );
			return false;
		}

		return $guest;
	}


	/**
	 * @deprecated
	 *
	 * @param $key
	 *
	 * @return Guest|bool
	 */
	static function get_by_key( $key ) {

		wc_deprecated_function( __METHOD__, '5.2.0' );

		if ( ! $key ) return false;

		$guest = new Guest();
		$guest->get_by( 'tracking_key', $key );

		if ( ! $guest->exists ) {
			return false;
		}

		return $guest;
	}


	/**
	 * @param Guest $guest
	 */
	static function update_cache( $guest ) {
		parent::update_cache( $guest );

		Cache::set( $guest->get_email(), $guest->get_id(), 'guest_email' );
	}


	/**
	 * @param Guest $guest
	 */
	static function clean_cache( $guest ) {
		parent::clean_cache( $guest );

		static::clear_cached_prop( $guest, 'email', 'guest_email' );
	}


	/**
	 * @param string $email
	 * @return Guest
	 */
	static function create( $email ) {

		$guest = new Guest();
		$guest->set_email( Clean::email( $email) );
		$guest->set_date_created( new DateTime() );
		$guest->save();

		return $guest;
	}

}

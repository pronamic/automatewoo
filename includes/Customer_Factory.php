<?php

namespace AutomateWoo;

use WC_Order;
use WP_User;

/**
 * @class Customer_Factory
 * @since 3.0.0
 */
class Customer_Factory extends Factory {

	/**
	 * Model class to use for an object.
	 *
	 * @var string
	 */
	public static $model = 'AutomateWoo\Customer';


	/**
	 * Get a customer.
	 *
	 * @param int $customer_id
	 *
	 * @return Customer|bool
	 */
	public static function get( $customer_id ) {
		return parent::get( $customer_id );
	}


	/**
	 * Get a customer from a user ID.
	 *
	 * @param int  $user_id
	 * @param bool $create  Create the customer record if it doesn't exist.
	 *
	 * @return Customer|bool Returns false if there is no user ID or customer record can't be created (create = true).
	 */
	public static function get_by_user_id( $user_id, $create = true ) {

		if ( ! $user_id ) {
			Logger::error( 'customer', 'No user ID provided for getting a customer.' );
			return false;
		}

		$user_id = Clean::id( $user_id );

		if ( Cache::exists( $user_id, 'customer_user_id' ) ) {
			return static::get( Cache::get( $user_id, 'customer_user_id' ) );
		}

		$customer = new Customer();
		$customer->get_by( 'user_id', $user_id );

		if ( $customer->exists ) {
			return $customer;
		}

		if ( $create ) {
			// attempt to create the customer
			$customer = static::create_from_user( $user_id );

			if ( $customer ) {
				return $customer;
			}
		}

		return false;
	}


	/**
	 * Get a customer object from a guest ID.
	 *
	 * @param int  $guest_id Guest ID.
	 * @param bool $create   Create the customer record if it doesn't exist.
	 *
	 * @return Customer|bool Returns false if there is no guest ID or customer record can't be created (create = true).
	 */
	public static function get_by_guest_id( $guest_id, $create = true ) {

		if ( ! $guest_id ) {
			Logger::error( 'customer', 'No guest ID provided for getting a customer.' );
			return false;
		}

		$guest_id = Clean::id( $guest_id );

		if ( Cache::exists( $guest_id, 'customer_guest_id' ) ) {
			return static::get( Cache::get( $guest_id, 'customer_guest_id' ) );
		}

		$customer = new Customer();
		$customer->get_by( 'guest_id', $guest_id );

		if ( $customer->exists ) {
			return $customer;
		}

		if ( $create ) {
			// attempt to create the customer
			$customer = static::create_from_guest( $guest_id );

			if ( $customer ) {
				return $customer;
			}
		}

		return false;
	}


	/**
	 * Get a customer object from an email address linked to a user or a guest.
	 *
	 * @param string $email
	 * @param bool   $create Create the customer record if it doesn't exist.
	 * @return Customer|bool Returns false if there is no valid email or customer record can't be created (create = true).
	 */
	public static function get_by_email( $email, $create = true ) {

		if ( ! is_email( $email ) ) {
			return false;
		}

		$email = Clean::email( $email );

		// check for matching user
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			return static::get_by_user_id( $user->ID, $create );
		}

		// check for matching guest
		$guest = Guest_Factory::get_by_email( $email );
		if ( $guest ) {
			return static::get_by_guest_id( $guest->get_id(), $create );
		}

		if ( $create ) {
			// create guest for new customer
			$guest = Guest_Factory::create( $email );
			return static::get_by_guest_id( $guest->get_id() );
		}

		return false;
	}


	/**
	 * Get a customer by key.
	 *
	 * @param string $key
	 *
	 * @return Customer|bool Returns false if there is no key or customer can't be found.
	 */
	public static function get_by_key( $key ) {

		if ( ! $key ) {
			Logger::error( 'customer', 'No key provided for getting a customer.' );
			return false;
		}

		$key = Clean::string( $key );

		if ( Cache::exists( $key, 'customer_key' ) ) {
			return static::get( Cache::get( $key, 'customer_key' ) );
		}

		$customer = new Customer();
		$customer->get_by( 'id_key', $key );

		if ( $customer->exists ) {
			return $customer;
		}

		return false;
	}


	/**
	 * Get a customer from a user object.
	 *
	 * @param WP_User|Order_Guest $user   User data item.
	 * @param bool                $create Create the customer record if it doesn't exist.
	 *
	 * @return Customer|bool Returns false if the data item is not valid or customer record can't be created (create = true).
	 */
	public static function get_by_user_data_item( $user, $create = true ) {
		if ( is_a( $user, 'WP_User' ) ) {
			return static::get_by_user_id( $user->ID, $create );
		} elseif ( is_a( $user, 'AutomateWoo\Order_Guest' ) ) {
			return static::get_by_email( $user->user_email, $create );
		}

		Logger::error( 'customer', 'No valid user object provided for getting a customer.' );
		return false;
	}


	/**
	 * Get a customer object from the customer/guest that purchased the order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param bool     $create Create the customer record if it doesn't exist.
	 * @param bool     $log_error Log an error if the order can't be found.
	 *
	 * @return Customer|bool Returns false if there is no order or customer record can't be created (create = true).
	 */
	public static function get_by_order( $order, $create = true, $log_error = true ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			if ( $log_error ) {
				Logger::error( 'customer', 'No valid order provided for getting a customer.' );
			}
			return false;
		}

		if ( $order->get_user_id() ) {
			return static::get_by_user_id( $order->get_user_id(), $create );
		} else {
			return static::get_by_email( $order->get_billing_email(), $create );
		}
	}


	/**
	 * Get a customer from a review.
	 *
	 * @param Review $review
	 * @param bool   $create Create the customer record if it doesn't exist.
	 *
	 * @return Customer|bool Returns false if there is no review or customer record can't be created (create = true).
	 */
	public static function get_by_review( $review, $create = true ) {
		if ( ! $review || ! is_a( $review, 'AutomateWoo\Review' ) ) {
			Logger::error( 'customer', 'No valid review provided for getting a customer.' );
			return false;
		}

		if ( $review->get_user_id() ) {
			return static::get_by_user_id( $review->get_user_id(), $create );
		} else {
			return static::get_by_email( $review->get_email(), $create );
		}
	}


	/**
	 * Create a customer record from a user ID.
	 *
	 * @param int $user_id
	 *
	 * @return Customer|bool Returns false if the user is not found.
	 */
	private static function create_from_user( $user_id ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			Logger::error( 'customer', sprintf( 'User %d not found when creating customer.', $user_id ) );
			return false;
		}

		$customer = new Customer();
		$customer->set_user_id( $user_id );
		$customer->set_key( static::generate_unique_customer_key() );
		$customer->save();

		return $customer;
	}


	/**
	 * Create a customer record from a guest ID.
	 *
	 * @param int $guest_id
	 *
	 * @return Customer|bool Returns false if the guest is not found.
	 */
	private static function create_from_guest( $guest_id ) {

		$guest = Guest_Factory::get( $guest_id );

		if ( ! $guest ) {
			Logger::error( 'customer', sprintf( 'Guest %d not found when creating customer.', $guest_id ) );
			return false;
		}

		$customer = new Customer();
		$customer->set_guest_id( $guest_id );
		$customer->set_key( static::generate_unique_customer_key() );
		$customer->save();

		return $customer;
	}


	/**
	 * Generates a unique customer key (which doesn't exist yet in the DB).
	 *
	 * @return string
	 */
	private static function generate_unique_customer_key() {

		$key = aw_generate_key( 20, false );

		$query = new Customer_Query();
		$query->where( 'id_key', $key );

		// If the customer ID already exists, then generate another one.
		if ( $query->has_results() ) {
			return static::generate_unique_customer_key();
		}

		return $key;
	}


	/**
	 * Updates a customer in the cache.
	 *
	 * @param Customer $customer
	 */
	public static function update_cache( $customer ) {
		parent::update_cache( $customer );

		if ( $customer->get_user_id() ) {
			Cache::set( $customer->get_user_id(), $customer->get_id(), 'customer_user_id' );
		}

		if ( $customer->get_guest_id() ) {
			Cache::set( $customer->get_guest_id(), $customer->get_id(), 'customer_guest_id' );
		}

		Cache::set( $customer->get_key(), $customer->get_id(), 'customer_key' );
	}


	/**
	 * Clears a customer from the cache.
	 *
	 * @param Customer $customer
	 */
	public static function clean_cache( $customer ) {
		parent::clean_cache( $customer );

		static::clear_cached_prop( $customer, 'user_id', 'customer_user_id' );
		static::clear_cached_prop( $customer, 'guest_id', 'customer_guest_id' );
		static::clear_cached_prop( $customer, 'id_key', 'customer_key' );
	}
}

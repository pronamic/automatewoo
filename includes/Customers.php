<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Customer (including guests) management class
 *
 * @class Customers
 * @since 3.0.0
 */
class Customers {


	static function init() {
		$self = 'AutomateWoo\Customers'; /** @var $self Customers */

		add_action( 'automatewoo/object/delete', [ $self, 'delete_customer_on_guest_delete' ] );
		add_action( 'delete_user', [ $self, 'delete_customer_on_user_delete' ] );
		add_action( 'user_register', [ $self, 'maybe_update_guest_customer_when_user_registers' ], 5 );

		add_action( 'clean_comment_cache', [ $self, 'clean_review_count_cache_on_clean_comment_cache' ] );
	}


	/**
	 * @param Model|Guest $object
	 */
	static function delete_customer_on_guest_delete( $object ) {
		if ( $object->object_type !== 'guest' ) {
			return;
		}

		if ( $customer = Customer_Factory::get_by_guest_id( $object->get_id(), false ) ) {
			$customer->delete();
		}
	}


	/**
	 * @param int $user_id
	 */
	static function delete_customer_on_user_delete( $user_id ) {
		if ( ! $user_id ) {
			return;
		}

		if ( $customer = Customer_Factory::get_by_user_id( $user_id, false ) ) {
			$customer->delete();
		}
	}


	/**
	 * Returns true if a guest was converted.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	static function maybe_update_guest_customer_when_user_registers( $user_id ) {
		$user = get_userdata( $user_id );

		if ( ! $user || ! $user->user_email ) {
			return false;
		}

		// if the guest and user have the same email address convert and delete them
		// we won't delete the guest record if the emails don't match, e.g. with a cookie matched guest
		if ( ! $guest = Guest_Factory::get_by_email( Clean::email( $user->user_email ) ) ) {
			return false;
		}

		self::convert_guest_to_registered_customer( $guest, $user );
		$guest->delete(); // clear all guest data (including cart)
		return true;
	}


	/**
	 * Convert guest customer to registered user customer.
	 *
	 * @param Guest $guest
	 * @param \WP_User $user
	 */
	static function convert_guest_to_registered_customer( $guest, $user ) {
		$guest_customer = Customer_Factory::get_by_guest_id( $guest->get_id(), false );

		if ( ! $guest_customer ) {
			return; // nothing to convert
		}

		$user_customer = Customer_Factory::get_by_user_id( $user->ID, false );

		if ( $user_customer ) {
			return; // user already exists, guest will just be deleted
		}

		// we have a guest customer that needs to be converted to a registered customer
		$guest_customer->set_guest_id( 0 );
		$guest_customer->set_user_id( $user->ID );
		$guest_customer->save();

		$guest_customer->clear_review_count_cache();

		do_action( 'automatewoo/customer/converted_guest_to_registered_customer', $guest_customer );
	}

	/**
	 * Clears persistent review count cache.
	 *
	 * @since 4.5
	 *
	 * @param int $comment_id
	 */
	static function clean_review_count_cache_on_clean_comment_cache( $comment_id ) {
		$review = Review_Factory::get( $comment_id );

		if ( ! $review ) {
			return;
		}

		$customer = $review->get_customer();

		if ( $customer ) {
			$customer->clear_review_count_cache();
		}
	}


}

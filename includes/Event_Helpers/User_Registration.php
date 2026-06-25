<?php

namespace AutomateWoo\Event_Helpers;

/**
 * @class User_Registration
 */
class User_Registration {

	/** @var bool */
	protected static $created_via_ultimate_members_signup = false;


	/**
	 * Register the user registration hooks.
	 */
	public static function init() {

		add_action( 'user_register', [ __CLASS__, 'user_created' ] );

		// for ultimate trigger on account approval
		add_action( 'um_post_registration_approved_hook', [ __CLASS__, 'user_registered' ], 100, 1 );
		add_action( 'um_after_user_is_approved', [ __CLASS__, 'user_registered' ], 100, 1 );
	}


	/**
	 * User has just been saved in database
	 *
	 * @param int $user_id
	 */
	public static function user_created( $user_id ) {

		// check for ultimate members signup, wait for approval
		if ( did_action( 'um_add_user_frontend' ) && self::$created_via_ultimate_members_signup === false ) {
			self::$created_via_ultimate_members_signup = true; // set in case another user is created in the same request
			return; // bail and wait for approval
		}

		self::user_registered( $user_id );
	}


	/**
	 * @param int $user_id
	 */
	public static function user_registered( $user_id ) {

		if ( get_user_meta( $user_id, '_aw_user_registered', true ) ) {
			return;
		}

		add_user_meta( $user_id, '_aw_user_registered', true );

		// User is fully registered, only fires once per user
		do_action( 'automatewoo/user_registered', (int) $user_id );
	}
}

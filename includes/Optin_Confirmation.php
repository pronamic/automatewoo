<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Email confirmation for a new communication signup.
 *
 * A signup for an address that is not already subscribed is held pending until
 * the address owner confirms it from a link sent to that address, so the
 * subscription (and any workflow that runs on it) happens only for an address
 * whose owner asked for it. Confirmation is on by default and can be turned off
 * with the `automatewoo/communication_signup/require_optin_confirmation` filter.
 * The marketing opt-in on a guest checkout, and on a new account created at
 * checkout or from the registration form, uses the same confirmation. So does
 * a later opt-in from an account created through WooCommerce, from an account
 * that took over an opted-out guest, or from an account whose customer changed
 * its email, until the address is confirmed by this link or by a password reset
 * link.
 *
 * @since 6.9.1
 * @since 6.9.6 Also used for the checkout and registration opt-ins, and for later opt-ins from new accounts and changed account emails.
 */
class Optin_Confirmation {

	/** Customer meta key holding the pending confirmation token. */
	const TOKEN_META = 'optin_confirmation_token';

	/** Customer meta key holding the time the confirmation was requested. */
	const REQUESTED_META = 'optin_confirmation_requested';

	/** Customer meta key holding the address a confirmation link was sent to. */
	const TOKEN_ADDRESS_META = 'optin_confirmation_address';

	/** How long a confirmation link stays valid, in seconds. */
	const TOKEN_TTL = 2 * DAY_IN_SECONDS;

	/** User meta key set on a new or changed account address until it is confirmed. */
	const ADDRESS_UNCONFIRMED_META = '_aw_optin_address_unconfirmed';

	/** User meta key holding the address whose earlier opt-out came with the account. */
	const INHERITED_OPT_OUT_ADDRESS_META = '_aw_optin_inherited_opt_out_address';

	/**
	 * Whether a new communication signup must be confirmed by email before it is subscribed.
	 *
	 * Also applies to the checkout and registration opt-ins.
	 *
	 * @since 6.9.1
	 *
	 * @return bool
	 */
	public static function is_required() {
		/**
		 * Filters whether a new communication signup must be confirmed by email before it is subscribed.
		 *
		 * Also applies to the marketing opt-in on a guest checkout, and on a new
		 * account created at checkout or from the registration form. An account
		 * created through WooCommerce, one that took over an opted-out guest, or one
		 * whose customer changed its email also needs confirmation for later opt-ins
		 * until its address is confirmed. When this returns false, those later
		 * opt-ins apply immediately and a change of email doesn't pause a
		 * subscription.
		 *
		 * @since 6.9.1
		 * @since 6.9.6 Also applies to the checkout and registration opt-ins, and to later opt-ins from new accounts and changed account emails.
		 *
		 * @param bool $required Whether email confirmation is required. Default true.
		 */
		return (bool) apply_filters( 'automatewoo/communication_signup/require_optin_confirmation', true );
	}

	/**
	 * Mark the address of a new account as unconfirmed.
	 *
	 * Set when WooCommerce creates the account, or when a new account takes over an
	 * opted-out guest, unless a store manager created it. Removed once the address
	 * is confirmed.
	 *
	 * @since 6.9.6
	 *
	 * @param int $user_id
	 * @return void
	 */
	public static function mark_address_unconfirmed( $user_id ) {
		if ( Permissions::can_manage() ) {
			return;
		}

		update_user_meta( $user_id, self::ADDRESS_UNCONFIRMED_META, '1' );

		$customer = Customer_Factory::get_by_user_id( $user_id, false );

		if ( $customer instanceof Customer && $customer->get_is_unsubscribed() ) {
			update_user_meta( $user_id, self::INHERITED_OPT_OUT_ADDRESS_META, Clean::email( $customer->get_email() ) );
		}
	}

	/**
	 * Mark the address as unconfirmed when a new account takes over an opted-out guest.
	 *
	 * This covers accounts created outside WooCommerce, some of which log the visitor
	 * in without an emailed link.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer The customer that was converted to the new account.
	 * @return void
	 */
	public static function mark_converted_guest_address_unconfirmed( $customer ) {
		if ( $customer->get_is_unsubscribed() ) {
			self::mark_address_unconfirmed( $customer->get_user_id() );
		}
	}

	/**
	 * Whether an opt-in by this customer has to be confirmed by email.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer
	 * @return bool
	 */
	public static function needs_address_confirmation( $customer ) {
		return self::is_required() && $customer->get_user_id() && get_user_meta( $customer->get_user_id(), self::ADDRESS_UNCONFIRMED_META, true );
	}

	/**
	 * Treat an address as confirmed once a password reset link sent to it has been used.
	 *
	 * @since 6.9.6
	 *
	 * @param \WP_User $user
	 * @return void
	 */
	public static function confirm_address_on_password_reset( $user ) {
		delete_user_meta( $user->ID, self::ADDRESS_UNCONFIRMED_META );
	}

	/**
	 * Treat the address as unconfirmed after a customer changes their own account email.
	 *
	 * Any earlier link stops working. An opt-out an address made before it had this
	 * account stays with that address: it is kept when the account leaves, and it
	 * carries over when the account moves to it. In opt-in mode, with confirmation
	 * on, a subscription is paused until the new address confirms it. Changes made
	 * by a store manager, or without a logged-in user, are left alone.
	 *
	 * @since 6.9.6
	 *
	 * @param int      $user_id
	 * @param \WP_User $old_user The user before the update.
	 * @return void
	 */
	public static function handle_account_email_change( $user_id, $old_user ) {
		if ( (int) $user_id !== get_current_user_id() || Permissions::can_manage() ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user || ! $old_user instanceof \WP_User || Clean::email( $user->user_email ) === Clean::email( $old_user->user_email ) ) {
			return;
		}

		$customer = Customer_Factory::get_by_user_id( $user_id );

		if ( ! $customer instanceof Customer ) {
			return;
		}

		if ( get_user_meta( $user_id, self::INHERITED_OPT_OUT_ADDRESS_META, true ) === Clean::email( $old_user->user_email ) ) {
			if ( $customer->get_is_unsubscribed() ) {
				self::keep_opt_out_with_address( Clean::email( $old_user->user_email ), $customer );
			}

			delete_user_meta( $user_id, self::INHERITED_OPT_OUT_ADDRESS_META );
		}

		// The address has already changed here, so this can't use has_pending().
		$had_pending = $customer->get_meta( self::TOKEN_META ) && self::is_within_lifetime( $customer );

		self::clear_pending( $customer );
		update_user_meta( $user_id, self::ADDRESS_UNCONFIRMED_META, '1' );

		$was_subscribed = $customer->get_is_subscribed();
		$guest          = Guest_Factory::get_by_email( Clean::email( $user->user_email ) );
		$guest_customer = $guest instanceof Guest ? Customer_Factory::get_by_guest_id( $guest->get_id(), false ) : false;

		if ( $guest_customer instanceof Customer && $guest_customer->get_is_unsubscribed() ) {
			self::copy_opt_out( $guest_customer, $customer );

			if ( $was_subscribed ) {
				self::add_email_change_notice( __( 'Your email address changed, so your subscription ended. To subscribe, please opt in again.', 'automatewoo' ) );
				return;
			}
		} elseif ( $was_subscribed && Options::optin_enabled() && self::is_required() ) {
			// Not an opt-out, so the opted out hook doesn't fire and no opt-out is recorded.
			$customer->set_is_subscribed( false );
			$customer->save();

			if ( self::start( $customer ) ) {
				self::add_email_change_notice( __( "Your email address changed, so please confirm your subscription. We've emailed a link to your new address.", 'automatewoo' ) );
			} else {
				self::add_email_change_notice( __( "Your email address changed, so your subscription is paused. We couldn't send the confirmation email.", 'automatewoo' ), 'error' );
			}

			return;
		}

		if ( $had_pending ) {
			self::add_email_change_notice( __( 'Your email address changed, so your earlier confirmation link no longer works. To subscribe, please opt in again.', 'automatewoo' ) );
		}
	}

	/**
	 * Record an opt-out that came with the account against the address it came from.
	 *
	 * @since 6.9.6
	 *
	 * @param string   $email    The address the account is leaving.
	 * @param Customer $customer The account's customer.
	 * @return void
	 */
	private static function keep_opt_out_with_address( $email, $customer ) {
		// An existing guest record already holds this address's own state.
		if ( Guest_Factory::get_by_email( $email ) instanceof Guest ) {
			return;
		}

		$guest_customer = Customer_Factory::get_by_guest_id( Guest_Factory::create( $email )->get_id() );

		if ( $guest_customer instanceof Customer ) {
			self::copy_opt_out( $customer, $guest_customer );
		}
	}

	/**
	 * Record an earlier opt-out on another record for the same address, with its original date.
	 *
	 * This isn't a new opt-out, so the opted out hook doesn't fire.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $from The record holding the opt-out.
	 * @param Customer $to   The record to copy it to.
	 * @return void
	 */
	private static function copy_opt_out( $from, $to ) {
		$to->set_is_subscribed( false );
		$to->set_is_unsubscribed( true );

		$date = $from->get_date_unsubscribed();

		if ( $date instanceof DateTime ) {
			$to->set_date_unsubscribed( $date );
		}

		$to->save();
	}

	/**
	 * Clear an opt-out kept for an address once its owner has confirmed an opt-in.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer The customer who confirmed, at their current address.
	 * @return void
	 */
	private static function clear_kept_opt_out( $customer ) {
		$guest          = $customer->get_user_id() ? Guest_Factory::get_by_email( $customer->get_email() ) : false;
		$guest_customer = $guest instanceof Guest ? Customer_Factory::get_by_guest_id( $guest->get_id(), false ) : false;

		if ( $guest_customer instanceof Customer && $guest_customer->get_is_unsubscribed() ) {
			$guest_customer->set_is_unsubscribed( false );
			$guest_customer->save();
		}
	}

	/**
	 * Tell the customer about an email change, on a request that can show notices.
	 *
	 * @since 6.9.6
	 *
	 * @param string $message
	 * @param string $type
	 * @return void
	 */
	private static function add_email_change_notice( $message, $type = 'success' ) {
		if ( WC()->session ) {
			wc_add_notice( $message, $type );
		}
	}

	/**
	 * Store a fresh confirmation token for the customer and email them the confirmation link.
	 *
	 * @since 6.9.1
	 * @since 6.9.6 Returns whether the email was sent.
	 *
	 * @param Customer $customer
	 * @return bool Whether the email was sent.
	 */
	public static function start( $customer ) {
		$token = aw_generate_key( 32 );

		$customer->update_meta( self::TOKEN_META, $token );
		$customer->update_meta( self::REQUESTED_META, time() );
		$customer->update_meta( self::TOKEN_ADDRESS_META, Clean::email( $customer->get_email() ) );

		return self::send_email( $customer, $token );
	}

	/**
	 * Whether the customer has a confirmation link that is still valid.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer
	 * @return bool
	 */
	public static function has_pending( $customer ) {
		if ( ! $customer->get_meta( self::TOKEN_META ) ) {
			return false;
		}

		return self::is_request_current( $customer );
	}

	/**
	 * Whether the customer's confirmation request is within its lifetime and for their current address.
	 *
	 * Links sent before the address was recorded have none.
	 *
	 * @param Customer $customer
	 * @return bool
	 */
	private static function is_request_current( $customer ) {
		if ( ! self::is_within_lifetime( $customer ) ) {
			return false;
		}

		$address = $customer->get_meta( self::TOKEN_ADDRESS_META );

		return ! $address || Clean::email( $customer->get_email() ) === $address;
	}

	/**
	 * Whether the customer's confirmation request is still within its lifetime.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer
	 * @return bool
	 */
	private static function is_within_lifetime( $customer ) {
		$requested = (int) $customer->get_meta( self::REQUESTED_META );

		return $requested && ( time() - $requested ) <= self::TOKEN_TTL;
	}

	/**
	 * Resolve the pending customer for a confirmation link when its token is valid and unexpired.
	 *
	 * The customer is found from the token alone, so the confirmation link never
	 * needs to carry the durable customer key.
	 *
	 * @since 6.9.1
	 *
	 * @param string $token Confirmation token from the confirmation link.
	 * @return Customer|false
	 */
	public static function get_pending_customer( $token ) {
		if ( ! $token ) {
			return false;
		}

		$customer = self::find_customer_by_token( (string) $token );

		if ( ! $customer ) {
			return false;
		}

		if ( ! self::is_request_current( $customer ) ) {
			return false;
		}

		return $customer;
	}

	/**
	 * Find the customer whose stored confirmation token matches the given one.
	 *
	 * The SQL match narrows on the indexed meta key, so it only scans pending
	 * confirmations. That match can be case-insensitive under the table
	 * collation, so each candidate is re-checked with a constant-time
	 * comparison, which is the authoritative one.
	 *
	 * @param string $token
	 * @return Customer|false
	 */
	private static function find_customer_by_token( $token ) {
		global $wpdb;

		$table = Database_Tables::get( 'customer-meta' )->get_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the interpolated table name is a trusted schema identifier, not user input.
				"SELECT customer_id, meta_value FROM {$table} WHERE meta_key = %s AND meta_value = %s",
				self::TOKEN_META,
				$token
			)
		);

		foreach ( $rows as $row ) {
			if ( hash_equals( (string) $row->meta_value, $token ) ) {
				return Customer_Factory::get( (int) $row->customer_id );
			}
		}

		return false;
	}

	/**
	 * Confirm the customer's opt-in, consuming the pending token atomically so
	 * concurrent confirmations opt in at most once.
	 *
	 * @since 6.9.1
	 * @since 6.9.6 Also marks the address as confirmed.
	 *
	 * @param Customer $customer
	 * @param string   $token    The confirmation token to consume.
	 * @return bool Whether this request consumed the token and opted the customer in.
	 */
	public static function confirm( $customer, $token ) {
		if ( ! self::claim_token( $customer, $token ) ) {
			return false;
		}

		$customer->opt_in();
		// Sync the meta cache after claim_token's raw DELETE (and clear the requested-at key).
		self::clear_pending( $customer );
		self::clear_kept_opt_out( $customer );

		if ( $customer->get_user_id() ) {
			delete_user_meta( $customer->get_user_id(), self::ADDRESS_UNCONFIRMED_META );
			delete_user_meta( $customer->get_user_id(), self::INHERITED_OPT_OUT_ADDRESS_META );
		}

		// Mirror the immediate signup path, which fires this hook after saving a
		// signup's preferences, so extensions that listen for it also run for a
		// confirmed signup. Only the hook is fired here: the confirmation page has
		// no tracking-preference control and attaches no visitor session.
		do_action( 'automatewoo/communication_page/save_preferences', $customer );

		return true;
	}

	/**
	 * Consume the pending token, reporting whether this call removed it.
	 *
	 * The token is already validated with hash_equals in get_pending_customer(),
	 * so this is the consume step, not the comparison. A conditional DELETE lets
	 * only one of two racing confirmations see a removed row.
	 *
	 * @param Customer $customer
	 * @param string   $token
	 * @return bool
	 */
	private static function claim_token( $customer, $token ) {
		global $wpdb;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the interpolated table and column are trusted schema identifiers, not user input.
				"DELETE FROM {$customer->get_meta_table_name()} WHERE {$customer->get_meta_object_id_column()} = %d AND meta_key = %s AND meta_value = %s",
				$customer->get_id(),
				self::TOKEN_META,
				$token
			)
		);

		return 1 === (int) $deleted;
	}

	/**
	 * Remove any stored confirmation token and the details stored with it.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 * @return void
	 */
	public static function clear_pending( $customer ) {
		$customer->delete_meta( self::TOKEN_META );
		$customer->delete_meta( self::REQUESTED_META );
		$customer->delete_meta( self::TOKEN_ADDRESS_META );
	}

	/**
	 * The URL a confirmation link points at.
	 *
	 * The link carries only the short-lived, single-use token. The durable
	 * customer key is deliberately absent: it never rotates, and anyone who
	 * sees the email could otherwise lift it into a permanent preferences
	 * credential.
	 *
	 * @param string $token
	 * @return string
	 */
	private static function get_confirmation_url( $token ) {
		return esc_url_raw(
			add_query_arg(
				[
					'aw-action' => 'confirm-optin',
					'token'     => $token,
				],
				home_url( '/' )
			)
		);
	}

	/**
	 * Send the confirmation email.
	 *
	 * @param Customer $customer
	 * @param string   $token
	 * @return bool Whether the email was sent.
	 */
	private static function send_email( $customer, $token ) {
		$url = self::get_confirmation_url( $token );

		$content  = wpautop( __( 'Thanks for signing up. Please confirm your subscription by opening the link below.', 'automatewoo' ) );
		$content .= wpautop( '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Confirm your subscription', 'automatewoo' ) . '</a>' );
		$content .= wpautop( __( "If you didn't request this, you can ignore this email.", 'automatewoo' ) );

		try {
			// Load WooCommerce's email classes: Mailer's inline styling instantiates
			// WC_Email. Mirrors how the workflow email path prepares the mailer.
			WC()->mailer();

			$mailer = new Mailer();
			$mailer->set_email( $customer->get_email() );
			$mailer->set_subject( __( 'Please confirm your subscription', 'automatewoo' ) );
			$mailer->set_heading( __( 'Confirm your subscription', 'automatewoo' ) );
			$mailer->set_content( $content );

			$sent = $mailer->send();
		} catch ( \Throwable $e ) {
			// The email runs inside the checkout, which it must not stop.
			$sent = false;
		}

		// A send failure does not change the visitor response. It is recorded for
		// diagnosis instead, and no address is logged. The token is removed because
		// nobody received it, so a later request can send a new one.
		if ( is_wp_error( $sent ) || ! $sent ) {
			self::clear_pending( $customer );
			Logger::error( 'communication-signup', 'An opt-in confirmation email could not be sent.' );

			return false;
		}

		return true;
	}
}

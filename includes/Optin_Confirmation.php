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
 *
 * @since 6.9.1
 */
class Optin_Confirmation {

	/** Customer meta key holding the pending confirmation token. */
	const TOKEN_META = 'optin_confirmation_token';

	/** Customer meta key holding the time the confirmation was requested. */
	const REQUESTED_META = 'optin_confirmation_requested';

	/** How long a confirmation link stays valid, in seconds. */
	const TOKEN_TTL = 2 * DAY_IN_SECONDS;

	/**
	 * Whether a new communication signup must be confirmed by email before it is subscribed.
	 *
	 * @since 6.9.1
	 *
	 * @return bool
	 */
	public static function is_required() {
		/**
		 * Filters whether a new communication signup must be confirmed by email before it is subscribed.
		 *
		 * @since 6.9.1
		 *
		 * @param bool $required Whether email confirmation is required. Default true.
		 */
		return (bool) apply_filters( 'automatewoo/communication_signup/require_optin_confirmation', true );
	}

	/**
	 * Store a fresh confirmation token for the customer and email them the confirmation link.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 * @return void
	 */
	public static function start( $customer ) {
		$token = aw_generate_key( 32 );

		$customer->update_meta( self::TOKEN_META, $token );
		$customer->update_meta( self::REQUESTED_META, time() );

		self::send_email( $customer, $token );
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

		$requested = (int) $customer->get_meta( self::REQUESTED_META );

		if ( ! $requested || ( time() - $requested ) > self::TOKEN_TTL ) {
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
	 * Remove any stored confirmation token.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 * @return void
	 */
	public static function clear_pending( $customer ) {
		$customer->delete_meta( self::TOKEN_META );
		$customer->delete_meta( self::REQUESTED_META );
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
	 * @return void
	 */
	private static function send_email( $customer, $token ) {
		$url = self::get_confirmation_url( $token );

		$content  = wpautop( __( 'Thanks for signing up. Please confirm your subscription by opening the link below.', 'automatewoo' ) );
		$content .= wpautop( '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Confirm your subscription', 'automatewoo' ) . '</a>' );
		$content .= wpautop( __( "If you didn't request this, you can ignore this email.", 'automatewoo' ) );

		// Load WooCommerce's email classes: Mailer's inline styling instantiates
		// WC_Email. Mirrors how the workflow email path prepares the mailer.
		WC()->mailer();

		$mailer = new Mailer();
		$mailer->set_email( $customer->get_email() );
		$mailer->set_subject( __( 'Please confirm your subscription', 'automatewoo' ) );
		$mailer->set_heading( __( 'Confirm your subscription', 'automatewoo' ) );
		$mailer->set_content( $content );

		$sent = $mailer->send();

		// A send failure does not change the visitor response. It is recorded for
		// diagnosis instead, and no address is logged.
		if ( is_wp_error( $sent ) || ! $sent ) {
			Logger::error( 'communication-signup', 'An opt-in confirmation email could not be sent.' );
		}
	}
}

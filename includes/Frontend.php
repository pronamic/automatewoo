<?php

namespace AutomateWoo;

/**
 * Checkout hooks class.
 *
 * Only loads on the checkout page.
 *
 * @since 4.0
 */
class Frontend {


	/**
	 * @return string
	 */
	public static function get_communication_page_legal_text() {
		$text = Options::communication_page_legal_text();

		if ( function_exists( 'wc_replace_policy_page_link_placeholders' ) ) {
			$text = wc_replace_policy_page_link_placeholders( $text );
		}

		$find_replace = [
			'[terms]'          => '',
			'[privacy_policy]' => '',
		];

		$text = str_replace( array_keys( $find_replace ), array_values( $find_replace ), $text );

		return apply_filters( 'automatewoo/communication_page/legal_text', $text );
	}


	/**
	 * @return Customer|false
	 */
	public static function get_current_customer() {
		if ( is_user_logged_in() ) {
			return Customer_Factory::get_by_user_id( get_current_user_id() );
		}

		return false;
	}


	/**
	 * If $customer is set a signed `aw_token` will be added to the link.
	 *
	 * @since 6.5.0 Added the $workflow_id parameter.
	 * @since 6.9.1 Emits a signed, purpose-bound `aw_token` instead of the
	 *              durable `customer_key`. The raw key is only emitted as a
	 *              fallback for intents a token cannot represent (an intent
	 *              not matching `^[A-Za-z0-9_-]*$`, e.g. from an add-on), so those
	 *              links keep working through the `customer_key` acceptance
	 *              path while it remains open.
	 *
	 * @param Customer|false $customer    Customer object.
	 * @param bool|string    $intent      Communication page intent. Any non-string value
	 *                                     (e.g. the default `false`, or `true`) means no
	 *                                     specific intent and yields a management link.
	 * @param int            $workflow_id Workflow that caused the opt-out.
	 * @return bool|string
	 */
	public static function get_communication_page_permalink( $customer = false, $intent = false, $workflow_id = 0 ) {
		$url = get_permalink( Options::communication_page_id() );
		if ( ! $url ) {
			return false;
		}

		if ( ! is_string( $intent ) ) {
			// Normalise rather than cast: `(string) true` is "1", which would
			// mint a token bound to the intent "1" instead of a management link.
			$intent = '';
		}

		$args        = [];
		$workflow_id = Clean::id( $workflow_id );

		if ( $customer ) {
			$token = Preferences_Token::generate( $customer, $intent, $workflow_id );

			if ( $token ) {
				$args['aw_token'] = $token;
			} else {
				// The intent cannot be represented in a token (it contains
				// characters outside `^[A-Za-z0-9_-]*$`). Fall back to the legacy
				// customer_key so third-party intents keep working while the
				// `automatewoo/preferences/accept_customer_key` window is open.
				// Warn once per request per distinct intent: a bulk send generates
				// this link once per email, and repeating an identical warning
				// thousands of times would flood the log without adding signal,
				// while collapsing different intents would hide which one is
				// falling back. A static local resets with each PHP request.
				static $warned_intents = [];

				if ( ! isset( $warned_intents[ $intent ] ) ) {
					$warned_intents[ $intent ] = true;
					Logger::warning(
						'preferences',
						sprintf( 'Communication page link for intent "%s" could not be tokenized; emitting a legacy customer_key. This intent will stop working when customer_key acceptance is disabled — use an intent matching ^[A-Za-z0-9_-]*$.', $intent )
					);
				}
				$args['customer_key'] = urlencode( $customer->get_key() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve existing query-arg encoding semantics for the customer key.
			}
		}

		if ( $intent ) {
			$args['intent'] = urlencode( $intent ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve existing query-arg encoding semantics for the intent value.
		}

		if ( $workflow_id ) {
			$args['workflow'] = $workflow_id;
		}

		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw( add_query_arg( $args, $url ) );
	}


	/**
	 * Only shows when using optin mode
	 */
	public static function output_signup_optin_checkbox() {
		if ( ! Options::optin_enabled() || ! Options::account_optin_enabled() ) {
			return;
		}

		aw_get_template( 'optin-checkbox.php' );
	}


	/**
	 * Only shows when using optin mode
	 */
	public static function output_checkout_optin_checkbox() {
		if ( ! Options::optin_enabled() || ! Options::checkout_optin_enabled() ) {
			return;
		}

		$customer = self::get_current_customer();

		if ( $customer && $customer->get_is_subscribed() ) {
			return; // customer already opted in
		}

		aw_get_template( 'optin-checkbox.php' );
	}


	/**
	 * @param int $order_id
	 */
	public static function process_checkout_optin( $order_id ) {
		if ( ! Options::optin_enabled() || ! Options::checkout_optin_enabled() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Opt-in is processed within WooCommerce's nonce-verified checkout/registration flow; presence-only isset() check.
		if ( ! isset( $_POST['automatewoo_optin'] ) ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		$customer->opt_in();
	}

	/**
	 * @param \WC_Order   $order
	 * @param \WP_Request $request
	 */
	public static function process_checkout_block_optin( $order, $request ) {
		if ( ! Options::optin_enabled() || ! Options::checkout_optin_enabled() ) {
			return;
		}

		if ( ! $order ) {
			return;
		}

		if ( empty( $request['extensions']['automatewoo']['optin'] ) ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		$customer->opt_in();
	}


	/**
	 * @param int $user_id
	 */
	public static function process_account_signup_optin( $user_id ) {
		if ( ! Options::optin_enabled() || ! Options::account_optin_enabled() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Opt-in is processed within WooCommerce's nonce-verified checkout/registration flow; presence-only isset() check.
		if ( ! isset( $_POST['woocommerce-register-nonce'] ) ) {
			return; // signup not from registration form
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Opt-in is processed within WooCommerce's nonce-verified checkout/registration flow; presence-only isset() check.
		if ( isset( $_POST['automatewoo_optin'] ) ) {
			$customer = Customer_Factory::get_by_user_id( $user_id );
			$customer->opt_in();
		}
	}
}

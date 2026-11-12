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
	 * Order meta key set when a checkout opt-in may still need confirming.
	 *
	 * @var string
	 */
	private const OPTIN_CONFIRMATION_REQUESTED_META = '_aw_optin_confirmation_requested';

	/**
	 * Orders with the block checkout opt-in ticked in this request, keyed by order ID.
	 *
	 * @var array<int, bool>
	 */
	private static $block_optin_orders = [];


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
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Opt-in is processed within WooCommerce's nonce-verified checkout/registration flow; presence-only isset() check.
		if ( ! isset( $_POST['automatewoo_optin'] ) ) {
			self::clear_optin_confirmation_requested( $order );
			return;
		}

		self::opt_in_order_customer( $order );
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
			self::clear_optin_confirmation_requested( $order );
			return;
		}

		// WooCommerce can still reject the order after this hook, so the opt-in is
		// handled in process_checkout_block_order().
		self::$block_optin_orders[ $order->get_id() ] = true;
	}

	/**
	 * Handle the opt-in recorded for a block checkout order once WooCommerce has validated it.
	 *
	 * @since 6.9.6
	 *
	 * @param \WC_Order $order
	 * @return void
	 */
	public static function process_checkout_block_order( $order ) {
		if ( ! $order instanceof \WC_Order || empty( self::$block_optin_orders[ $order->get_id() ] ) ) {
			return;
		}

		unset( self::$block_optin_orders[ $order->get_id() ] );

		self::opt_in_order_customer( $order );
	}

	/**
	 * Remove the opt-in message from an order, for a retry with the checkbox unticked.
	 *
	 * @param \WC_Order $order
	 * @return void
	 */
	private static function clear_optin_confirmation_requested( $order ) {
		if ( $order->get_meta( self::OPTIN_CONFIRMATION_REQUESTED_META ) ) {
			$order->delete_meta_data( self::OPTIN_CONFIRMATION_REQUESTED_META );
			$order->save_meta_data();
		}
	}

	/**
	 * Opt in the customer who placed an order with the checkout opt-in checkbox ticked.
	 *
	 * An order placed from an account that existed before this checkout opts
	 * that customer in, unless its address still needs confirming. Any other
	 * order, a guest order or one that created its account during this checkout,
	 * goes through request_optin_confirmation().
	 *
	 * @since 6.9.6
	 *
	 * @param \WC_Order $order
	 * @return void
	 */
	private static function opt_in_order_customer( $order ) {
		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer instanceof Customer ) {
			return;
		}

		if ( $order->get_user_id() && ! did_action( 'woocommerce_created_customer' ) && ! Optin_Confirmation::needs_address_confirmation( $customer ) ) {
			$customer->opt_in();
			return;
		}

		$requested = self::request_optin_confirmation( $customer );

		if ( $requested && Optin_Confirmation::is_required() ) {
			$order->update_meta_data( self::OPTIN_CONFIRMATION_REQUESTED_META, '1' );
			$order->save_meta_data();
		}
	}

	/**
	 * Tell the shopper on the order received page that an opt-in may need confirming.
	 *
	 * The message is the same whatever the state of the address. It is left out
	 * only when a confirmation email could not be sent.
	 *
	 * @since 6.9.6
	 *
	 * @param string          $text  The order received text.
	 * @param \WC_Order|false $order The order, if there is one.
	 * @return string
	 */
	public static function filter_order_received_text( $text, $order ) {
		if ( ! $order instanceof \WC_Order || ! $order->get_meta( self::OPTIN_CONFIRMATION_REQUESTED_META ) || $order->has_status( [ 'cancelled', 'refunded' ] ) ) {
			return $text;
		}

		return $text . ' ' . esc_html__( "If your email subscription still needs confirming, we've emailed you a link to complete it.", 'automatewoo' );
	}

	/**
	 * Tell a newly registered customer that an opt-in may need confirming.
	 *
	 * @since 6.9.6
	 *
	 * @param string $redirect The URL to redirect to after registering.
	 * @return string
	 */
	public static function add_registration_optin_notice( $redirect ) {
		wc_add_notice( __( "Thanks! We've received your request. If your subscription still needs confirming, we've emailed you a link to complete it.", 'automatewoo' ) );

		return $redirect;
	}

	/**
	 * Hold an opt-in pending until it is confirmed from an emailed link.
	 *
	 * Used for an opt-in from a guest order or from an account whose address still
	 * needs confirming, the same as the communication signup form. No new link is
	 * sent while an earlier one is still valid. When confirmation is turned off,
	 * the customer is opted in only if the address had no account before this
	 * request and has never opted out.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer
	 * @return bool False only when a confirmation email could not be sent.
	 */
	private static function request_optin_confirmation( $customer ) {
		if ( $customer->get_is_subscribed() || Optin_Confirmation::has_pending( $customer ) ) {
			return true;
		}

		if ( Optin_Confirmation::is_required() ) {
			return Optin_Confirmation::start( $customer );
		}

		$new_account = (bool) did_action( 'woocommerce_created_customer' );

		if ( ( ! $customer->get_user_id() || $new_account ) && ! $customer->get_is_unsubscribed() ) {
			$customer->opt_in();
		}

		return true;
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
			if ( $customer instanceof Customer ) {
				$requested = self::request_optin_confirmation( $customer );

				if ( $requested && Optin_Confirmation::is_required() ) {
					// Logging the new customer in reloads the session, so a notice added now would be lost.
					add_filter( 'woocommerce_registration_redirect', [ __CLASS__, 'add_registration_optin_notice' ] );
				}
			}
		}
	}
}

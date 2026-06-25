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
	 * If $customer is set the customer key will be added to the link.
	 *
	 * @since 6.5.0 Added the $workflow_id parameter.
	 *
	 * @param Customer|false $customer    Customer object.
	 * @param bool|string    $intent      Communication page intent.
	 * @param int            $workflow_id Workflow that caused the opt-out.
	 * @return bool|string
	 */
	public static function get_communication_page_permalink( $customer = false, $intent = false, $workflow_id = 0 ) {
		$url = get_permalink( Options::communication_page_id() );
		if ( ! $url ) {
			return false;
		}

		$args = [];

		if ( $customer ) {
			$args['customer_key'] = urlencode( $customer->get_key() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve existing query-arg encoding semantics for the customer key.
		}

		if ( $intent ) {
			$args['intent'] = urlencode( $intent ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve existing query-arg encoding semantics for the intent value.
		}

		$workflow_id = Clean::id( $workflow_id );
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

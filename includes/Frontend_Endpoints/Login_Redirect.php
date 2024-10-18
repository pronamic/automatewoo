<?php

namespace AutomateWoo\Frontend_Endpoints;

use AutomateWoo\Frontend_Endpoints;

/**
 * Class Login_Redirect
 *
 * Contains login redirect functionality.
 * Some URLs require the user to be logged in before loading them such as the subscription early renewal URL.
 * This class provides a way to get the user to login before redirecting them to the final URL.
 *
 * @since 4.9.1
 */
class Login_Redirect {

	/**
	 * Init.
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'maybe_add_redirect_notice' ] );
		add_action( 'woocommerce_login_form_end', [ $this, 'maybe_add_redirect_input' ] );
		add_filter( 'woocommerce_login_redirect', [ $this, 'filter_login_form_redirect' ] );
	}

	/**
	 * Handle the login redirect endpoint.
	 *
	 * Should be called only in \AutomateWoo\Frontend_Endpoints::handle.
	 */
	public function handle_endpoint() {
		$redirect = esc_url_raw( aw_get_url_var( 'redirect-url' ) );

		if ( is_user_logged_in() ) {
			Frontend_Endpoints::redirect_while_preserving_url_args( $redirect, [ 'redirect-url' ] );
		} else {
			$redirect = add_query_arg( [ 'aw-redirect-after-login' => rawurlencode( $redirect ) ], wc_get_page_permalink( 'myaccount' ) );
			Frontend_Endpoints::redirect_while_preserving_url_args( $redirect, [ 'redirect-url' ] );
		}
	}

	/**
	 * Get a URL that will prompt the user to login before redirecting them to the supplied URL.
	 *
	 * @param string $redirect_url The URL the user will be sent to after login.
	 *
	 * @return string
	 */
	public function get_login_redirect_url( $redirect_url ) {
		return add_query_arg(
			[
				'aw-action'    => 'login-redirect',
				'redirect-url' => rawurlencode( $redirect_url ),
			],
			home_url()
		);
	}

	/**
	 * Add a notice informing the user they must login.
	 */
	public function maybe_add_redirect_notice() {
		$redirect = esc_url_raw( aw_get_url_var( 'aw-redirect-after-login' ) );

		if ( ! $redirect || ! is_account_page() || is_user_logged_in() ) {
			return;
		}

		wc_add_notice( __( 'Please login to your account.', 'automatewoo' ) );
	}

	/**
	 * Adds the login redirect URL to the login form as a hidden input.
	 */
	public function maybe_add_redirect_input() {
		$redirect = aw_get_url_var( 'aw-redirect-after-login' );

		if ( ! $redirect ) {
			return;
		}

		?>
		<input type="hidden" name="aw-redirect-after-login" value="<?php echo esc_url( $redirect ); ?>">
		<?php
	}

	/**
	 * Filter the woocommerce_login_redirect value and add our own redirect if it exists.
	 *
	 * @param string $original_redirect
	 *
	 * @return string
	 */
	public function filter_login_form_redirect( $original_redirect ) {
		$posted_redirect = wp_unslash( aw_get_post_var( 'aw-redirect-after-login' ) );

		return $posted_redirect ?: $original_redirect;
	}
}

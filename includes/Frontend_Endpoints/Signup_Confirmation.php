<?php

namespace AutomateWoo\Frontend_Endpoints;

use AutomateWoo\Clean;
use AutomateWoo\Optin_Confirmation;
use AutomateWoo\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Endpoint and page for confirming a communication signup opt-in.
 *
 * A subscribe from the signup form is held pending until the address owner
 * confirms it from a link sent to that address. Reached as `aw-action=confirm-optin`,
 * the dispatcher delegates here: a valid POST submission confirms the opt-in and
 * a GET only renders the confirmation page. This class owns the endpoint dispatch
 * and the confirmation-page rendering; the token lifecycle lives in
 * {@see \AutomateWoo\Optin_Confirmation}.
 *
 * @since 6.9.1
 */
class Signup_Confirmation {

	/**
	 * Nonce action for the confirmation submission.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'automatewoo-confirm-optin';

	/**
	 * Render state for the confirmation page: '', 'confirm', 'confirmed' or 'invalid'.
	 *
	 * @var string
	 */
	private static $state = '';

	/**
	 * Handle a communication signup confirmation link.
	 *
	 * The subscription is confirmed only on a POST submission of the confirmation
	 * page (carrying a fresh nonce plus the emailed token). A GET only renders the
	 * confirmation page and does not confirm the subscription.
	 *
	 * @since 6.9.1
	 *
	 * @return void
	 */
	public static function handle_endpoint() {
		$token = Clean::string( aw_request( 'token' ) );

		if ( self::is_confirmed_submission() ) {
			$customer = Optin_Confirmation::get_pending_customer( $token );

			// Report 'confirmed' only when this request performed the opt-in. A
			// concurrent confirmation may have consumed the token first.
			if ( $customer && Optin_Confirmation::confirm( $customer, $token ) ) {
				self::$state = 'confirmed';
			} else {
				self::$state = 'invalid';
			}
		} else {
			self::$state = Optin_Confirmation::get_pending_customer( $token ) ? 'confirm' : 'invalid';
		}

		// The theme and main query aren't ready this early (wp_loaded), so defer the
		// page to template_redirect.
		add_action( 'template_redirect', [ __CLASS__, 'render' ] );
	}

	/**
	 * Whether the current request is a valid confirmation submission.
	 *
	 * @return bool
	 */
	private static function is_confirmed_submission() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		if ( 'POST' !== $method ) {
			return false;
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Render the confirmation as a real page's content.
	 *
	 * Rendered on `template_redirect` once the query is resolved, so it inherits
	 * the theme's header, footer and layout and works on block and classic themes
	 * alike. The request is sent to the communication page to host the confirmation
	 * as that page's content. With no communication page configured, or if the
	 * configured page id cannot be landed on, a standalone page is rendered instead.
	 *
	 * @since 6.9.1
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! self::$state ) {
			return;
		}

		$page_id  = Options::communication_page_id();
		$host_url = $page_id ? get_permalink( $page_id ) : '';

		// Render standalone when there is no communication page to host the
		// confirmation, or when a redirect already ran and did not land on that
		// page (a misconfigured page id), so a bad page id cannot loop the redirect.
		if ( ! $host_url || ( ! is_page( $page_id ) && aw_request( 'aw-optin-hosted' ) ) ) {
			self::render_standalone();
			return;
		}

		if ( ! is_page( $page_id ) ) {
			wp_safe_redirect( add_query_arg( array_merge( aw_get_query_args(), [ 'aw-optin-hosted' => '1' ] ), $host_url ) );
			exit;
		}

		// Run late so the returned markup is not reprocessed by wpautop.
		add_filter( 'the_content', [ __CLASS__, 'get_content' ], 99 );
	}

	/**
	 * Render the confirmation as a standalone page.
	 *
	 * Fallback for when no communication page is configured to host it. The
	 * layout is plainer on a block theme, but the confirmation stays usable.
	 *
	 * @return void
	 */
	private static function render_standalone() {
		get_header();

		aw_get_template(
			'optin-confirmation.php',
			[
				'state'      => self::$state,
				'nonce_name' => self::NONCE_ACTION,
				'home_url'   => home_url( '/' ),
			]
		);

		get_footer();
		exit;
	}

	/**
	 * Replace the host page content with the confirmation page.
	 *
	 * @since 6.9.1
	 *
	 * @param string $content The post content being rendered.
	 * @return string
	 */
	public static function get_content( $content ) {
		if ( ! is_main_query() || ! in_the_loop() || ! self::$state ) {
			return $content;
		}

		$page_id = Options::communication_page_id();

		// With a communication page configured, replace only that page's content.
		if ( $page_id && ! is_page( $page_id ) ) {
			return $content;
		}

		remove_filter( 'the_content', [ __CLASS__, 'get_content' ], 99 );

		ob_start();

		aw_get_template(
			'optin-confirmation.php',
			[
				'state'      => self::$state,
				'nonce_name' => self::NONCE_ACTION,
				'home_url'   => home_url( '/' ),
			]
		);

		return ob_get_clean();
	}
}

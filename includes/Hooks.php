<?php

namespace AutomateWoo;

/**
 * @class Hooks
 * @since 2.6.7
 */
class Hooks {


	/**
	 * Initialize Application hooks
	 */
	public static function init() {
		$self = __CLASS__; /** @var $self Hooks (for IDE) */

		// addons
		add_action( 'automatewoo/addons/activate', [ $self, 'activate_addon' ] );

		// frontend action endpoints
		add_action( 'wp_loaded', [ $self, 'check_for_action_endpoint' ] );
		add_action( 'wp_loaded', [ $self, 'maybe_handle_frontend_form' ] );

		// email
		add_filter( 'automatewoo_email_content', 'wpautop' );

		// pre-submit
		add_action( 'wp_enqueue_scripts', [ $self, 'maybe_enqueue_presubmit_js' ], 20 );
		add_action( 'automatewoo/ajax/capture_email', [ 'AutomateWoo\PreSubmit', 'ajax_capture_email' ] );
		add_action( 'automatewoo/ajax/capture_checkout_field', [ 'AutomateWoo\PreSubmit', 'ajax_capture_checkout_field' ] );

		// conversions
		add_action( 'automatewoo/async/order_created', [ 'AutomateWoo\Conversions', 'check_order_for_conversion' ], 20 );

		add_action( 'get_header', [ 'AutomateWoo\Language', 'make_language_persistent' ] );

		// object caching
		add_action( 'automatewoo/object/load', [ 'AutomateWoo\Factories', 'update_object_cache' ] );
		// clean cache on object create, as a blank cache value is used for carts, for example
		add_action( 'automatewoo/object/create', [ 'AutomateWoo\Factories', 'clean_object_cache' ] );
		add_action( 'automatewoo/object/create', [ 'AutomateWoo\Factories', 'update_object_cache' ] );
		add_action( 'automatewoo/object/update', [ 'AutomateWoo\Factories', 'clean_object_cache' ] );
		add_action( 'automatewoo/object/delete', [ 'AutomateWoo\Factories', 'clean_object_cache' ] );

		// system check
		add_action( 'admin_init', [ 'AutomateWoo\System_Checks', 'maybe_schedule_check' ], 20 );
		add_action( 'admin_notices', [ 'AutomateWoo\System_Checks', 'maybe_display_notices' ] );
		add_action( 'automatewoo/system_check', [ 'AutomateWoo\System_Checks', 'run_system_check' ] );

		// pages
		add_action( 'template_redirect', [ $self, 'maybe_init_pages' ] );
		add_action( 'template_redirect', [ $self, 'init_shortcodes' ] );

		add_action( 'automatewoo_updated_async', 'flush_rewrite_rules' );

		add_action( 'wp_enqueue_scripts', [ $self, 'register_scripts' ] );

		// optin
		add_action( 'woocommerce_checkout_after_terms_and_conditions', [ 'AutomateWoo\Frontend', 'output_checkout_optin_checkbox' ] );
		add_action( 'woocommerce_register_form', [ 'AutomateWoo\Frontend', 'output_signup_optin_checkbox' ], 20 );
		add_action( 'woocommerce_checkout_order_processed', [ 'AutomateWoo\Frontend', 'process_checkout_optin' ], 20 );

		if ( Integrations::is_woocommerce_blocks_active( '7.2.0' ) ) {
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ 'AutomateWoo\Frontend', 'process_checkout_block_optin' ], 10, 2 );
		} else {
			add_action( 'woocommerce_blocks_checkout_update_order_from_request', [ 'AutomateWoo\Frontend', 'process_checkout_block_optin' ], 10, 2 );
		}

		add_action( 'woocommerce_created_customer', [ 'AutomateWoo\Frontend', 'process_account_signup_optin' ], 20 );

		// workflow fatal error monitor
		add_action( 'automatewoo/workflow/before_run', [ 'AutomateWoo\Workflow_Fatal_Error_Monitor', 'attach' ] );
		add_action( 'automatewoo_after_workflow_run', [ 'AutomateWoo\Workflow_Fatal_Error_Monitor', 'detach' ] );

		if ( Language::is_multilingual() ) {
			add_action( 'automatewoo/action/before_preview', [ 'AutomateWoo\Preview_Data', 'add_customer_language_filter' ] );
			add_action( 'automatewoo/action/after_preview', [ 'AutomateWoo\Preview_Data', 'remove_customer_language_filter' ] );
		}
	}

	/**
	 * Init shortcodes. Only called on frontend.
	 *
	 * @since 4.5.2
	 */
	public static function init_shortcodes() {
		add_shortcode( 'automatewoo_communication_preferences', [ 'AutomateWoo\Communication_Page', 'output_preferences_shortcode' ] );
		add_shortcode( 'automatewoo_communication_signup', [ 'AutomateWoo\Communication_Page', 'output_signup_form' ] );
	}

	/**
	 * Activates an addon
	 *
	 * @param string $addon_id The addon ID
	 */
	public static function activate_addon( $addon_id ) {
		$addon = Addons::get( $addon_id );
		if ( $addon ) {
			$addon->activate();
		}
	}


	/**
	 * Action endpoints
	 */
	public static function check_for_action_endpoint() {

		// We are not processing any action here so no need for a nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['aw-action'] ) || wp_doing_ajax() || is_admin() ) {
			return;
		}

		Frontend_Endpoints::handle();
	}


	/**
	 * Action endpoints
	 */
	public static function maybe_handle_frontend_form() {

		// We are not processing any action here so no need for a nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) || empty( $_POST['action'] ) ) {
			return;
		}

		Frontend_Form_Handler::handle();
	}


	/**
	 * Maybe print pre-submit js
	 */
	public static function maybe_enqueue_presubmit_js() {
		if ( ! Options::presubmit_capture_enabled() || is_user_logged_in() ) {
			return;
		}

		switch ( AW()->options()->guest_email_capture_scope ) {
			case 'checkout':
				if ( ! is_checkout() ) {
					return;
				}
				break;
		}

		wp_localize_script( 'automatewoo-presubmit', 'automatewoo_presubmit_params', PreSubmit::get_js_params() );

		wp_enqueue_script( 'automatewoo-presubmit' );
	}


	/**
	 * Load plugin frontend pages
	 */
	public static function maybe_init_pages() {
		switch ( get_the_ID() ) {
			case Options::communication_page_id():
			case Options::signup_page_id():
				Communication_Page::init();
				break;
		}
	}


	/**
	 * Register Application Scripts and Styles
	 */
	public static function register_scripts() {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$suffix = '';
		} else {
			$suffix = '.min';
		}

		wp_register_script( 'automatewoo-presubmit', AW()->url( "/assets/js/automatewoo-presubmit$suffix.js" ), [ 'jquery' ], AW()->version, true );

		wp_register_style( 'automatewoo-main', AW()->url( '/assets/css/automatewoo-main.css' ), [], AW()->version );
		wp_register_style( 'automatewoo-communication-page', AW()->url( '/assets/css/automatewoo-communication-page.css' ), [ 'automatewoo-main' ], AW()->version );
	}
}

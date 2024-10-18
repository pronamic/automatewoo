<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_General class.
 */
class Settings_Tab_General extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'general';
		$this->name           = __( 'General', 'automatewoo' );
		$this->show_tab_title = false;
	}

	/**
	 * Load tab settings.
	 */
	public function load_settings() {

		$this->section_start(
			'privacy',
			__( 'Marketing opt-in', 'automatewoo' ),
			__( 'AutomateWoo can either be in opt-in mode or opt-out mode. Opt-in means customers must opt-in before email and SMS can be sent to them. Opt-out means email and SMS will be sent automatically until the customer chooses to opt-out. All transactional workflows are excluded from this.', 'automatewoo' )
		);

		$this->add_setting(
			'optin_mode',
			[
				'type'              => 'select',
				'title'             => __( 'Customer opt-in mode', 'automatewoo' ),
				'options'           => [
					'optin'  => __( 'Opt-in (recommended)', 'automatewoo' ),
					'optout' => __( 'Opt-out', 'automatewoo' ),
				],
				'custom_attributes' => [
					'data-automatewoo-bind' => 'optin_mode',
				],
			]
		);

		$this->add_setting(
			'enable_checkout_optin',
			[
				'type'               => 'checkbox',
				'title'              => __( 'Opt-in checkbox locations', 'automatewoo' ),
				'desc'               => __( 'Show on checkout page.', 'automatewoo' ),
				'wrapper_class'      => 'aw-settings-row--checkbox-group-start',
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'optin_mode=optin',
				],
			]
		);

		$this->add_setting(
			'enable_account_signup_optin',
			[
				'type'               => 'checkbox',
				'desc'               => __( 'Show on account sign up page.', 'automatewoo' ),
				'wrapper_class'      => 'aw-settings-row--checkbox-group',
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'optin_mode=optin',
				],
			]
		);

		$this->add_setting(
			'optin_checkbox_text',
			[
				'title'              => __( 'Opt-in checkbox text', 'automatewoo' ),
				'type'               => 'textarea',
				'set_default'        => true,
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'optin_mode=optin',
				],
			]
		);

		$this->section_end( 'privacy' );

		$this->section_start(
			'session_tracking',
			__( 'Session tracking', 'automatewoo' ),
			__( "Session tracking uses cookies to remember users when they are not signed in. This means carts can be tracked when the user is signed out. This is also used by the Refer A Friend add-on to reduce fraud. Since these cookies may not be considered 'essential' for the function of the website, users in some regions may need to give consent before these cookies are set.", 'automatewoo' )
		);

		$this->add_setting(
			'session_tracking_enabled',
			[
				'type'              => 'checkbox',
				'title'             => __( 'Enable session tracking', 'automatewoo' ),
				'autoload'          => true,
				'custom_attributes' => [
					'data-automatewoo-bind' => 'session_tracking',
				],
			]
		);

		$this->add_setting(
			'session_tracking_requires_cookie_consent',
			[
				'type'               => 'checkbox',
				'title'              => __( 'Require cookie consent', 'automatewoo' ),
				'desc'               => __( 'Disable session tracking until consent is given. Requires a cookie consent plugin.', 'automatewoo' ),
				'desc_tip'           => __( 'AutomateWoo does not add a notice asking for cookie consent. Please use a cookie consent plugin or custom code for this.', 'automatewoo' ),
				'autoload'           => true,
				'custom_attributes'  => [
					'data-automatewoo-bind' => 'require_cookie_consent',
				],
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'session_tracking',
				],
			]
		);

		$this->add_setting(
			'session_tracking_consent_cookie_name',
			[
				'title'              => __( 'Consent cookie name', 'automatewoo' ),
				'desc_tip'           => __( 'Insert the name of the cookie added by your cookie consent solution when a user gives consent. Please note that this is case sensitive. If you are unsure what this is please contact your developer.', 'automatewoo' ),
				'type'               => 'text',
				'required'           => true,
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'require_cookie_consent',
				],
			]
		);

		$this->add_setting(
			'enable_presubmit_data_capture',
			[
				'type'               => 'checkbox',
				'title'              => __( 'Enable pre-submit data capture', 'automatewoo' ),
				'desc'               => __( 'Capture guest customer data before forms are submitted e.g. during checkout. We recommend leaving this disabled to comply with GDPR.', 'automatewoo' ),
				'autoload'           => true,
				'custom_attributes'  => [
					'data-automatewoo-bind' => 'enable_presubmit',
				],
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'session_tracking',
				],
			]
		);

		$this->add_setting(
			'guest_email_capture_scope',
			[
				'type'               => 'select',
				'title'              => __( 'Where should pre-submit data capture be enabled?', 'automatewoo' ),
				'tooltip'            => __( 'Determines which pages have javascript code inserted for email capture.', 'automatewoo' ) . '<br><br>' . __( "If set to All Pages you can add the CSS class 'automatewoo-capture-guest-email' to enable email capture on custom form fields.", 'automatewoo' ),
				'options'            => [
					'checkout' => __( 'Checkout Only', 'automatewoo' ),
					'all'      => __( 'All Pages', 'automatewoo' ),
				],
				'wrapper_attributes' => [
					'data-automatewoo-show' => 'enable_presubmit',
				],
			]
		);

		$this->section_end( 'session_tracking' );

		$this->section_start(
			'commpage',
			__( 'Communication preferences page', 'automatewoo' ),
			/* translators: %1$s communication preferences shortcode, %2$s communication signup shortcode */
			sprintf( __( 'The communication preferences page is where customers can opt-in or opt-out of workflows. It must contain the shortcode %1$s. To set up a signup page use the shortcode %2$s and specify the page in the setting below.', 'automatewoo' ), '<code>[automatewoo_communication_preferences]</code>', '<code>[automatewoo_communication_signup]</code>' )
		);

		$this->add_setting(
			'communication_preferences_page_id',
			[
				'title'    => __( 'Communication preferences page', 'automatewoo' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'required' => true,
			]
		);

		$this->add_setting(
			'communication_signup_page_id',
			[
				'title'    => __( 'Communication signup page (optional)', 'automatewoo' ),
				'desc_tip' => __( 'The signup page is similar to the preferences page but allows customers to enter their email address.', 'automatewoo' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
			]
		);

		$this->add_setting(
			'enable_communication_account_tab',
			[
				'type'  => 'checkbox',
				'title' => __( 'Enable account tab', 'automatewoo' ),
				'desc'  => __( 'Show Communication tab in My Account area', 'automatewoo' ),
			]
		);

		$this->add_setting(
			'communication_page_legal_text',
			[
				'title'             => __( 'Legal text', 'automatewoo' ),
				'type'              => 'textarea',
				'desc_tip'          => sprintf(
					/* translators: List of shortcodes. */
					__( 'This text is shown above the form submit button. Use the following shortcodes to add dynamic content: %s', 'automatewoo' ),
					'[privacy_policy] [terms]'
				),
				'set_default'       => true,
				'custom_attributes' => [
					'rows' => 5,
				],
			]
		);

		$this->section_end( 'commpage' );

		$this->section_start( 'email', __( 'Email sender options', 'automatewoo' ) );

		$this->add_setting(
			'email_from_name',
			[
				'title'       => __( 'From name', 'automatewoo' ),
				'desc_tip'    => __( 'How the sender name appears in outgoing AutomateWoo emails. If blank the WooCommerce sender name will be used.', 'automatewoo' ),
				'type'        => 'text',
				'placeholder' => get_option( 'woocommerce_email_from_name' ),
			]
		);

		$this->add_setting(
			'email_from_address',
			[
				'title'       => __( 'From email', 'automatewoo' ),
				'desc_tip'    => __( 'How the sender email appears in outgoing AutomateWoo emails. If blank the WooCommerce sender email will be used.', 'automatewoo' ),
				'type'        => 'text',
				'placeholder' => get_option( 'woocommerce_email_from_address' ),
			]
		);

		$this->section_end( 'email' );

		$this->section_start( 'misc', __( 'Misc', 'automatewoo' ) );

		$this->add_setting(
			'conversion_window',
			[
				'title'    => __( 'Conversion tracking window', 'automatewoo' ),
				'desc_tip' => __( 'Sets the number of days after a workflow runs that a new order can be considered a conversion. Default value is 14.', 'automatewoo' ),
				'type'     => 'number',
			]
		);

		$this->add_setting(
			'clean_expired_coupons',
			[
				'title' => __( 'Automatically delete expired coupons', 'automatewoo' ),
				'desc'  => __( 'Coupons generated by AutomateWoo will be deleted 14 days after they expire.', 'automatewoo' ),
				'type'  => 'checkbox',
			]
		);

		$this->section_end( 'misc' );
	}
}

return new Settings_Tab_General();

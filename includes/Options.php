<?php

namespace AutomateWoo;

/**
 * @class Options
 * @since 2.0.2
 *
 * @property string $version
 *
 * @property bool $abandoned_cart_enabled
 * @property int $abandoned_cart_timeout
 * @property string $guest_email_capture_scope (checkout,all)
 * @property bool $clean_expired_coupons
 * @property bool $clear_inactive_carts_after
 * @property bool $abandoned_cart_includes_pending_orders
 *
 * @property bool $email_from_name
 * @property bool $email_from_address
 *
 * @property bool $twilio_integration_enabled
 * @property string $twilio_from
 * @property string $twilio_auth_id
 * @property string $twilio_auth_token
 *
 * @property bool $campaign_monitor_enabled
 * @property bool $campaign_monitor_api_key
 * @property bool $campaign_monitor_client_id
 *
 * @property bool $active_campaign_integration_enabled
 * @property string $active_campaign_api_url
 * @property string $active_campaign_api_key
 *
 * @property string $bitly_api
 * @property bool $bitly_shorten_sms_links
 *
 * @property int $conversion_window
 *
 * @property bool $enable_background_system_check
 * @property bool $delete_data_on_uninstall
 */
class Options extends Options_API {

	/** @var string */
	public $prefix = 'automatewoo_';


	/**
	 * Options constructor.
	 */
	public function __construct() {
		$this->defaults = [
			'optin_mode'                               => 'optin',
			'enable_checkout_optin'                    => 'yes',
			'enable_account_signup_optin'              => 'yes',
			'optin_checkbox_text'                      => __( 'I want to receive updates about products and promotions.', 'automatewoo' ),
			'session_tracking_enabled'                 => 'yes',
			'session_tracking_requires_cookie_consent' => 'no',
			'enable_communication_account_tab'         => 'no',
			'communication_page_legal_text'            => __( 'You can update these options at any time by clicking the unsubscribe link in the footer of any email you receive from us, or in your account area. By clicking below, you agree that we may process your information in accordance with our [terms] and [privacy_policy].', 'automatewoo' ),
			'enable_presubmit_data_capture'            => 'no',

			'abandoned_cart_enabled'                   => 'yes',
			'abandoned_cart_timeout'                   => 15,
			'clear_inactive_carts_after'               => 60,
			'guest_email_capture_scope'                => 'checkout',
			'clean_expired_coupons'                    => 'yes',
			'abandoned_cart_includes_pending_orders'   => 'no',

			'twilio_integration_enabled'               => 'no',
			'active_campaign_integration_enabled'      => false,
			'campaign_monitor_enabled'                 => false,
			'mailchimp_integration_enabled'            => false,
			'conversion_window'                        => 14,
			'enable_background_system_check'           => true,
			'bitly_shorten_sms_links'                  => 'no',
			'delete_data_on_uninstall'                 => 'no',
			'log_retention_months'                     => 0,
			'non_production_workflow_override'         => 'no',
		];
	}


	/**
	 * Returns the version of the database to handle migrations.
	 *
	 * Is autoloaded.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public static function database_version() {
		return Clean::string( self::get( 'version' ) );
	}

	/**
	 * Returns the stored version of the plugin files. Used to log when file updates occur.
	 *
	 * Is autoloaded.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public static function file_version() {
		return Clean::string( self::get( 'file_version' ) );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function optin_enabled() {
		return self::get( 'optin_mode' ) === 'optin';
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function session_tracking_enabled() {
		return (bool) self::get( 'session_tracking_enabled' );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function session_tracking_requires_cookie_consent() {
		return (bool) self::get( 'session_tracking_requires_cookie_consent' );
	}


	/**
	 * @since 4.0
	 * @return string
	 */
	public static function session_tracking_consent_cookie_name() {
		return Clean::string( self::get( 'session_tracking_consent_cookie_name' ) );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function presubmit_capture_enabled() {
		return self::get( 'session_tracking_enabled' ) && self::get( 'enable_presubmit_data_capture' );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function abandoned_cart_enabled() {
		return (bool) self::get( 'abandoned_cart_enabled' );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function checkout_optin_enabled() {
		return (bool) self::get( 'enable_checkout_optin' );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function account_optin_enabled() {
		return (bool) self::get( 'enable_account_signup_optin' );
	}


	/**
	 * @since 4.0
	 * @return string
	 */
	public static function optin_checkbox_text() {
		return trim( wp_kses_post( self::get( 'optin_checkbox_text' ) ) );
	}


	/**
	 * @since 4.0
	 * @return int
	 */
	public static function communication_page_id() {
		return Clean::id( self::get( 'communication_preferences_page_id' ) );
	}


	/**
	 * @since 4.0
	 * @return int
	 */
	public static function signup_page_id() {
		return Clean::id( self::get( 'communication_signup_page_id' ) );
	}


	/**
	 * @since 4.0
	 * @return string
	 */
	public static function communication_page_legal_text() {
		return trim( wp_kses_post( self::get( 'communication_page_legal_text' ) ) );
	}


	/**
	 * @since 4.0
	 * @return bool
	 */
	public static function communication_account_tab_enabled() {
		return (bool) self::get( 'enable_communication_account_tab' );
	}

	/**
	 * Get mailchimp_enabled option.
	 *
	 * @since 4.4
	 *
	 * @return bool
	 */
	public static function mailchimp_enabled() {
		return (bool) self::get( 'mailchimp_integration_enabled' );
	}

	/**
	 * Get mailchimp_api_key option.
	 *
	 * @since 4.4
	 *
	 * @return string
	 */
	public static function mailchimp_api_key() {
		return trim( Clean::string( self::get( 'mailchimp_api_key' ) ) );
	}

	/**
	 * Get active_campaign_integration_enabled option.
	 *
	 * @since 5.8.5
	 *
	 * @return bool
	 */
	public static function activecampaign_enabled(): bool {
		return (bool) self::get( 'active_campaign_integration_enabled' );
	}

	/**
	 * Get twilio_integration_enabled option.
	 *
	 * @since 5.8.5
	 *
	 * @return bool
	 */
	public static function twilio_enabled(): bool {
		return (bool) self::get( 'twilio_integration_enabled' );
	}

	/**
	 * Get bitly_shorten_sms_links option.
	 *
	 * @since 5.8.5
	 *
	 * @return bool
	 */
	public static function bitly_enabled(): bool {
		return (bool) self::get( 'bitly_shorten_sms_links' );
	}

	/**
	 * Get campaign_monitor_enabled option.
	 *
	 * @since 5.8.5
	 *
	 * @return bool
	 */
	public static function campaign_monitor_enabled(): bool {
		return (bool) self::get( 'campaign_monitor_enabled' );
	}

	/**
	 * Get non_production_workflow_override option.
	 *
	 * @since 6.5.0
	 *
	 * @return bool
	 */
	public static function non_production_workflow_override(): bool {
		return (bool) self::get( 'non_production_workflow_override' );
	}
}

<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use MailPoet\API\API;

/**
 * @class Integrations
 */
class Integrations {

	const REQUIRED_SUBSCRIPTIONS_VERSION       = '2.5';
	const REQUIRED_BOOKINGS_VERSION            = '1.15.35';
	const REQUIRED_MEMBERSHIPS_VERSION         = '1.7';
	const REQUIRED_POINTS_AND_REWARDS_VERSION  = '1.6.15';
	const REQUIRED_WOOCOMMERCE_BLOCKS_VERSION  = '6.3.0';
	const REQUIRED_DEPOSITS_VERSION            = '1.4';
	const REQUIRED_FREE_GIFT_COUPONS_VERSION   = '2.0.0';
	const REQUIRED_SENSEI_LMS_VERSION          = '4.8.0';
	const REQUIRED_SENSEI_CERTIFICATES_VERSION = '2.3.0';
	const REQUIRED_SENSEI_PRO_VERSION          = '1.8.0';

	/** @var Integration_Mailchimp */
	private static $mailchimp;

	/** @var Integration_ActiveCampaign */
	private static $activecampaign;

	/** @var Integration_Campaign_Monitor */
	private static $campaign_monitor;

	/** @var Integration_Twilio */
	private static $twilio;

	/** @var Integration_Bitly */
	private static $bitly;

	/** @var Integration_Mailpoet */
	private static $mailpoet;


	/**
	 * Is the WooCommerce Subscriptions plugin active or
	 * is the WC_Subscriptions_Core_Plugin class available?
	 *
	 * @since 4.5.0
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	static function is_subscriptions_active( $min_version = self::REQUIRED_SUBSCRIPTIONS_VERSION ) {
		if ( ! class_exists( '\WC_Subscriptions' ) && ! class_exists( '\WC_Subscriptions_Core_Plugin' ) ) {
			return false;
		}

		$version = class_exists( '\WC_Subscriptions_Core_Plugin' )
			? \WC_Subscriptions_Core_Plugin::instance()->get_plugin_version()
			: \WC_Subscriptions::$version;

		return version_compare( $version, $min_version, '>=' );
	}

	/**
	 * Check if MailPoet is active
	 *
	 * @since 5.6.4
	 *
	 * @return bool
	 */
	public static function is_mailpoet_active() {
		return defined( 'MAILPOET_VERSION' );
	}

	/**
	 * Check if the MailPoet customizer for WooCommerce emails is enabled
	 *
	 * @since 5.6.4
	 *
	 * @return bool
	 */
	public static function is_mailpoet_overriding_styles() {
		if ( ! self::is_mailpoet_active() || ! class_exists( '\MailPoet\Settings\SettingsController' ) ) {
			return false;
		}

		return '1' === \MailPoet\Settings\SettingsController::getInstance()->get( 'woocommerce.use_mailpoet_editor' );
	}

	/**
	 * Check if MailPoet API is active
	 *
	 * @since 5.6.10
	 *
	 * @return bool
	 */
	public static function is_mailpoet_api_active() {
		return self::is_mailpoet_active() && class_exists( '\MailPoet\API\API' );
	}

	/**
	 * Get the Mailpoet integration single instance.
	 *
	 * @since 5.6.10
	 *
	 * @return Integration_Mailpoet|false
	 * @throws \Exception If API version is invalid.
	 */
	static function mailpoet() {
		if ( ! isset( self::$mailpoet ) ) {

			if ( ! self::is_mailpoet_api_active() ) {
				return false;
			}

			self::$mailpoet = new Integration_Mailpoet( API::MP( 'v1' ) );
		}

		return self::$mailpoet;
	}

	/**
	 * @return bool
	 */
	static function is_wpml() {
		return class_exists('SitePress');
	}


	/**
	 * @return bool
	 */
	static function is_woo_pos() {
		return class_exists('WC_POS');
	}



	/**
	 * @return bool
	 */
	static function is_memberships_enabled() {
		if ( ! function_exists( 'wc_memberships' ) ) return false;
		if ( version_compare( wc_memberships()->get_version(), self::REQUIRED_MEMBERSHIPS_VERSION, '<' ) ) return false;
		return true;
	}


	/**
	 * @return bool
	 */
	static function is_mc4wp() {
		return defined( 'MC4WP_VERSION' );
	}


	/**
	 * @return Integration_Twilio|false
	 */
	static function get_twilio() {
		if ( ! AW()->options()->twilio_integration_enabled ) {
			return false;
		}

		if ( ! isset( self::$twilio ) ) {
			self::$twilio = new Integration_Twilio(
				Clean::string( AW()->options()->twilio_from ),
				Clean::string( AW()->options()->twilio_auth_id ),
				Clean::string( AW()->options()->twilio_auth_token )
			);
		}
		return self::$twilio;
	}


	/**
	 * @return Integration_Bitly|false
	 */
	static function get_bitly() {
		if ( ! AW()->options()->bitly_api ) {
			return false;
		}
		if ( ! isset( self::$bitly ) ) {
			self::$bitly = new Integration_Bitly( Clean::string( AW()->options()->bitly_api ) );
		}
		return self::$bitly;
	}


	/**
	 * @return Integration_Mailchimp|false
	 */
	static function mailchimp() {
		if ( ! isset( self::$mailchimp ) ) {
			if ( Options::mailchimp_enabled() && Options::mailchimp_api_key() ) {
				self::$mailchimp = new Integration_Mailchimp( Options::mailchimp_api_key() );
			}
			else {
				self::$mailchimp = false;
			}
		}

		return self::$mailchimp;
	}


	/**
	 * @return Integration_ActiveCampaign
	 */
	static function activecampaign() {
		if ( ! isset( self::$activecampaign ) ) {
			$api_url = trim( Clean::string( AW()->options()->active_campaign_api_url ) );
			$api_key = trim( Clean::string( AW()->options()->active_campaign_api_key ) );
			self::$activecampaign = new Integration_ActiveCampaign( $api_url, $api_key );
		}

		return self::$activecampaign;
	}


	/**
	 * @return Integration_Campaign_Monitor
	 */
	static function campaign_monitor() {
		if ( ! isset( self::$campaign_monitor ) ) {
			$api_key = trim( Clean::string( AW()->options()->campaign_monitor_api_key ) );
			$client_id = trim( Clean::string( AW()->options()->campaign_monitor_client_id ) );
			self::$campaign_monitor = new Integration_Campaign_Monitor( $api_key, $client_id );
		}

		return self::$campaign_monitor;
	}

	/**
	 * is_points_rewards_active method.
	 *
	 * @since 4.5.0
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	static function is_points_rewards_active( $min_version = self::REQUIRED_POINTS_AND_REWARDS_VERSION ) {
		if ( ! class_exists( '\WC_Points_Rewards' ) ) {
			return false;
		}
		return version_compare( \WC_Points_Rewards::VERSION, $min_version, '>=' );
	}

	/**
	 * is_woocommerce_blocks_active method.
	 *
	 * @since 5.6.0
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	static function is_woocommerce_blocks_active( $min_version = self::REQUIRED_WOOCOMMERCE_BLOCKS_VERSION ) {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
			return false;
		}
		return version_compare( \Automattic\WooCommerce\Blocks\Package::get_version(), $min_version, '>=' );
	}

	/**
	 * Is the WooCommerce Deposits plugin active?
	 *
	 * @since 4.8.0
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function is_deposits_active( $min_version = self::REQUIRED_DEPOSITS_VERSION ) {
		if ( ! defined( 'WC_DEPOSITS_VERSION' ) ) {
			return false;
		}
		return version_compare( WC_DEPOSITS_VERSION, $min_version, '>=' );
	}

	/**
	 * is_free_gift_coupons_active method.
	 *
	 * @since 4.8.4
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	static function is_free_gift_coupons_active( $min_version = self::REQUIRED_FREE_GIFT_COUPONS_VERSION ) {
		if ( ! class_exists( '\WC_Free_Gift_Coupons' ) ) {
			return false;
		}
		return version_compare( \WC_Free_Gift_Coupons::$version, $min_version, '>=' );
	}


	/**
	 * Is WooCommerce Bookings active?
	 *
	 * @since 5.3.0
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function is_bookings_active( string $min_version = self::REQUIRED_BOOKINGS_VERSION ): bool {
		return defined( 'WC_BOOKINGS_VERSION' ) && version_compare( WC_BOOKINGS_VERSION, $min_version, '>=' );
	}


	/**
	 * @deprecated in favour of Integrations::is_subscriptions_active()
	 *
	 * @return bool
	 */
	static function subscriptions_enabled() {
		wc_deprecated_function( __METHOD__, '5.2.0', 'is_subscriptions_active' );

		return self::is_subscriptions_active();
	}

	/**
	 * Is the Sensei LMS plugin active?
	 *
	 * @since 5.6.10
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function is_sensei_lms_active( $min_version = self::REQUIRED_SENSEI_LMS_VERSION ) {
		if ( ! defined( 'SENSEI_LMS_VERSION' ) ) {
			return false;
		}
		return version_compare( SENSEI_LMS_VERSION, $min_version, '>=' );
	}

	/**
	 * Is the Sensei Certificates plugin active?
	 *
	 * @since 5.6.10
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function is_sensei_certificates_active( $min_version = self::REQUIRED_SENSEI_CERTIFICATES_VERSION ) {
		if ( ! defined( 'SENSEI_CERTIFICATES_VERSION' ) ) {
			return false;
		}
		return version_compare( SENSEI_CERTIFICATES_VERSION, $min_version, '>=' );
	}

	/**
	 * Is the Sensei Pro plugin active?
	 *
	 * @since 5.6.10
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function is_sensei_pro_active( $min_version = self::REQUIRED_SENSEI_PRO_VERSION ) {
		if ( ! defined( 'SENSEI_PRO_VERSION' ) ) {
			return false;
		}
		return version_compare( SENSEI_PRO_VERSION, $min_version, '>=' );
	}
}

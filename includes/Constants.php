<?php

namespace AutomateWoo;

/**
 * Class to define AutomateWoo constants.
 *
 * @class Constants
 * @package AutomateWoo
 */
class Constants {

	/**
	 * Initialize AW constants
	 */
	public static function init() {
		self::set_defaults();
	}

	/**
	 * Set defaults
	 */
	public static function set_defaults() {

		if ( ! defined( 'AW_PREVENT_WORKFLOWS' ) ) {
			define( 'AW_PREVENT_WORKFLOWS', false );
		}

		if ( ! defined( 'AUTOMATEWOO_DISABLE_ASYNC_CUSTOMER_NEW_ACCOUNT' ) ) {
			define( 'AUTOMATEWOO_DISABLE_ASYNC_CUSTOMER_NEW_ACCOUNT', false );
		}

		if ( ! defined( 'AUTOMATEWOO_DISABLE_ASYNC_SUBSCRIPTION_STATUS_CHANGED' ) ) {
			define( 'AUTOMATEWOO_DISABLE_ASYNC_SUBSCRIPTION_STATUS_CHANGED', false );
		}

		if ( ! defined( 'AUTOMATEWOO_DISABLE_ASYNC_ORDER_STATUS_CHANGED' ) ) {
			define( 'AUTOMATEWOO_DISABLE_ASYNC_ORDER_STATUS_CHANGED', false );
		}

		if ( ! defined( 'AUTOMATEWOO_LOG_ASYNC_EVENTS' ) ) {
			define( 'AUTOMATEWOO_LOG_ASYNC_EVENTS', false );
		}

		if ( ! defined( 'AUTOMATEWOO_ENABLE_INSTANT_EVENT_DISPATCHING' ) ) {
			// Default value was changed to true in 4.9.0
			define( 'AUTOMATEWOO_ENABLE_INSTANT_EVENT_DISPATCHING', true );
		}

		if ( ! defined( 'AUTOMATEWOO_LOG_SENT_SMS' ) ) {
			define( 'AUTOMATEWOO_LOG_SENT_SMS', false );
		}

		if ( ! defined( 'AUTOMATEWOO_BACKGROUND_PROCESS_DEBUG' ) ) {
			define( 'AUTOMATEWOO_BACKGROUND_PROCESS_DEBUG', false );
		}

		/**
		 * Values used to display notices to warn of planned changes to the minimum requirements.
		 *
		 * @since 4.9.5
		 */
		// The AutomateWoo release that will implement the indicated changes.
		if ( ! defined( 'AUTOMATEWOO_NOTICE_AW_VER' ) ) {
			define( 'AUTOMATEWOO_NOTICE_AW_VER', '5.6.0' );
		}
		// The new WordPress minimum required version after the changes.
		if ( ! defined( 'AUTOMATEWOO_NOTICE_WP_VER' ) ) {
			define( 'AUTOMATEWOO_NOTICE_WP_VER', '5.8.0' );
		}
		// The new WooCommerce require version after the changes.
		if ( ! defined( 'AUTOMATEWOO_NOTICE_WC_VER' ) ) {
			define( 'AUTOMATEWOO_NOTICE_WC_VER', '6.7.0' );
		}
	}
}

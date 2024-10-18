<?php

namespace AutomateWoo\Admin\Analytics;

use AutomateWoo\Admin\Analytics;

/**
 * AutomateWoo Analytics.
 * Formerly AutomateWoo > Reports.
 *
 * @since 5.6.3
 */
class Rest_API {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Setup Analytics.
	 * Register controllers and data stores.
	 */
	public static function setup() {
		if ( self::is_enabled() ) {

			// REST API Controllers.
			add_filter( 'woocommerce_admin_rest_controllers', array( __CLASS__, 'add_rest_api_controllers' ) );

			// Register data stores.
			add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ) );

		}
	}

	/**
	 * Whether or not the Rest APIs for Analytic reports are enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return Analytics::is_enabled();
	}

	/**
	 * Adds Analytics REST contollers.
	 * To be used with `woocommerce_admin_rest_controllers` filter.
	 *
	 * @param  array $controllers
	 * @return array Extended with AW Analytics controllers.
	 */
	public static function add_rest_api_controllers( $controllers ) {
		$controllers[] = 'AutomateWoo\Admin\Analytics\Rest_API\Conversions\Controller';
		$controllers[] = 'AutomateWoo\Admin\Analytics\Rest_API\Conversions\Stats\Controller';
		$controllers[] = 'AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking\Stats_Controller';
		$controllers[] = 'AutomateWoo\Admin\Analytics\Rest_API\Unsubscribers\Stats_Controller';
		$controllers[] = 'AutomateWoo\Admin\Analytics\Rest_API\Workflow_Runs\Stats_Controller';

		return $controllers;
	}

	/**
	 * Register Analytics data stores.
	 * To be used with `woocommerce_data_stores` filter.
	 *
	 * @param  array $stores
	 * @return array Extended with AW Analytics stores.
	 */
	public static function register_data_stores( $stores ) {
		$stores['report-conversions-list']     = 'AutomateWoo\Admin\Analytics\Rest_API\Conversions\Store';
		$stores['report-conversions-stats']    = 'AutomateWoo\Admin\Analytics\Rest_API\Conversions\Stats\Store';
		$stores['report-email-tracking-stats'] = 'AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking\Data_Store';
		$stores['report-unsubscribers-stats']  = 'AutomateWoo\Admin\Analytics\Rest_API\Unsubscribers\Data_Store';
		$stores['report-workflow-runs-stats']  = 'AutomateWoo\Admin\Analytics\Rest_API\Workflow_Runs\Data_Store';

		return $stores;
	}
}

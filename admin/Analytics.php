<?php

namespace AutomateWoo\Admin;

use Automattic\WooCommerce\Admin\PageController;

/**
 * AutomateWoo Analytics.
 * Formerly AutomateWoo > Reports.
 *
 * @since 5.6.1
 */
class Analytics {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Setup Analytics.
	 * Add report items and register scripts.
	 */
	public static function setup() {
		if ( self::is_enabled() ) {
			// Analytics init.
			add_filter( 'woocommerce_analytics_report_menu_items', array( __CLASS__, 'add_report_menu_item' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_script' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_style' ) );
		}
	}

	/**
	 * Add "Bundles" as a Analytics submenu item.
	 *
	 * @param  array $report_pages  Report page menu items.
	 * @return array
	 */
	public static function add_report_menu_item( $report_pages ) {

		$report_pages[] = array(
			'id'     => 'automatewoo-analytics-runs-by-date',
			'title'  => self::get_report_menu_title( __( 'Workflows', 'automatewoo' ) ),
			'parent' => 'woocommerce-analytics',
			'path'   => '/analytics/automatewoo-runs-by-date',
		);
		$report_pages[] = array(
			'id'     => 'automatewoo-analytics-email-tracking',
			'title'  => self::get_report_menu_title( __( 'Email & SMS Tracking', 'automatewoo' ) ),
			'parent' => 'woocommerce-analytics',
			'path'   => '/analytics/automatewoo-email-tracking',
		);
		$report_pages[] = array(
			'id'     => 'automatewoo-analytics-conversions',
			'title'  => self::get_report_menu_title( __( 'Conversions', 'automatewoo' ) ),
			'parent' => 'woocommerce-analytics',
			'path'   => '/analytics/automatewoo-conversions',
		);
		return $report_pages;
	}

	/**
	 * Get an Analytics menu title with a self-contained AutomateWoo icon.
	 *
	 * @since 6.5.0
	 *
	 * @param string $title Menu title.
	 *
	 * @return string
	 */
	private static function get_report_menu_title( string $title ): string {
		$icon = '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="10.575 193.737 611 439" style="display:inline-block;height:1em;width:1.3918em;margin-right:.5em;vertical-align:text-bottom;fill:currentColor">'
			. '<g>'
			. '<polygon points="149.569,432.962 208.498,432.962 179.093,352.517"/>'
			. '<path d="M570.625,197.025H63.375c-27.373,0-49.725,22.353-49.725,49.725v333.5'
			. 'c0,27.373,22.353,49.725,49.725,49.725h507.25c27.373,0,49.725-22.352,49.725-49.725'
			. 'v-333.5C620.35,219.377,597.998,197.025,570.625,197.025z M240.054,519.502l-17.81-48.648'
			. 'h-86.541l-17.69,48.648H74.264l81.759-213.004h48.052l81.042,213.004H240.054z M496.807,519.502'
			. 'h-42.672l-48.649-151.685l-48.649,151.685h-43.271l-59.168-213.004h43.868l39.087,146.545'
			. 'l47.215-146.545h42.911l47.574,147.143l38.369-147.143h42.314L496.807,519.502z"/>'
			. '</g>'
			. '</svg>';

		return $icon . esc_html( $title );
	}

	/**
	 * Register analytics JS.
	 */
	public static function register_script() {
		if ( ! PageController::is_admin_page() ) {
			return;
		}

		$script_asset = require AW()->admin_path( '/assets/build/analytics.asset.php' );

		wp_register_script(
			'automatewoo-analytics',
			AW()->admin_assets_url( '/build/analytics.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Load JS translations.
		wp_set_script_translations( 'automatewoo-analytics', 'automatewoo', AW()->path( '/languages' ) );

		// Enqueue script.
		wp_enqueue_script( 'automatewoo-analytics' );
	}

	/**
	 * Register analytics CSS.
	 */
	public static function register_style() {
		if ( PageController::is_admin_page() ) {
			wp_enqueue_style(
				'automatewoo-analytics',
				AW()->admin_assets_url( '/build/analytics.css' ),
				[ 'wc-admin-app' ],
				AW()->version
			);
		}
	}

	/**
	 * Whether or not the new Analytics reports are enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$is_enabled = WC()->is_wc_admin_active();

		/**
		 * Whether AutomateWoo's analytics reports should be added to the WooCommerce Analytics menu.
		 *
		 * @filter automatewoo/admin/analytics_enabled
		 */
		return (bool) apply_filters( 'automatewoo/admin/analytics_enabled', $is_enabled );
	}
}

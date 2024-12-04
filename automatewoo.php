<?php
/**
 * Plugin Name: AutomateWoo
 * Plugin URI: https://automatewoo.com
 * Description: Powerful marketing automation for your WooCommerce store.
 * Version: 6.1.3
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 * Text Domain: automatewoo
 * Domain Path: /languages
 * Tested up to: 6.7
 * Requires Plugins: woocommerce
 * WC requires at least: 7.9
 * WC tested up to: 9.4
 * Woo: 4652610:f6f1f8a56a16a3715b30b21fb557e78f
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package AutomateWoo
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

define( 'AUTOMATEWOO_SLUG', 'automatewoo' );
define( 'AUTOMATEWOO_VERSION', '6.1.3' ); // WRCS: DEFINED_VERSION.
define( 'AUTOMATEWOO_FILE', __FILE__ );
define( 'AUTOMATEWOO_PATH', __DIR__ );
define( 'AUTOMATEWOO_MIN_PHP_VER', '7.4.0' );
define( 'AUTOMATEWOO_MIN_WP_VER', '5.9' );
define( 'AUTOMATEWOO_MIN_WC_VER', '7.9.0' );

/**
 * AutomateWoo loader.
 *
 * @since 2.9
 */
class AutomateWoo_Loader {

	/**
	 * Contains load errors.
	 *
	 * @var array
	 */
	public static $errors = array();

	/**
	 * Init loader.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ), 8 );

		// Ensure core before AutomateWoo add-ons.
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 8 );

		add_action( 'activate_plugin', array( __CLASS__, 'load_by_cli' ), 8 );
		// Load translations even if plugin requirements aren't met
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		// Subscribe to automated translations.
		add_action( 'woocommerce_translations_updates_for_automatewoo', '__return_true' );

		// Declare compatibility for WooCommerce features.
		add_action( 'before_woocommerce_init', [ __CLASS__, 'declare_feature_compatibility' ] );

		/**
		 * Trigger "automatewoo_activated" action after activation using option workaround.
		 *
		 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/#process-flow
		 */
		register_activation_hook( __FILE__, [ __CLASS__, 'activation' ] );
	}

	/**
	 * Loads plugin.
	 */
	public static function load() {
		if ( self::check() ) {
			add_action( 'admin_init', [ __CLASS__, 'do_activation_action' ], 20 );

			require_once __DIR__ . '/vendor/autoload.php';
			AutomateWoo::instance();
		}
	}

	/**
	 * Loads plugin when using WP CLI.
	 *
	 * @param string $plugin Plugin file.
	 */
	public static function load_by_cli( $plugin ): void {
		if ( self::check() && ( defined( 'WP_CLI' ) && WP_CLI ) && $plugin === 'automatewoo/automatewoo.php' ) {
			require_once __DIR__ . '/vendor/autoload.php';
			( AutomateWoo::instance() )->init();
			AutomateWoo\Installer::admin_init();
		}
	}

	/**
	 * Loads plugin textdomain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'automatewoo', false, 'automatewoo/languages' );
	}

	/**
	 * Checks if the plugin should load.
	 *
	 * @return bool
	 */
	public static function check() {
		$passed = true;

		if ( version_compare( phpversion(), AUTOMATEWOO_MIN_PHP_VER, '<' ) ) {
			$passed         = false;
			self::$errors[] = function () {
				/* translators: Plugin name. */
				$inactive_text = '<strong>' . sprintf( __( '%s is inactive.', 'automatewoo' ), __( 'AutomateWoo', 'automatewoo' ) ) . '</strong>';
				/* translators: %1$s inactive plugin text, %2$s minimum PHP version */
				return sprintf( __( '%1$s The plugin requires PHP version %2$s or newer.', 'automatewoo' ), $inactive_text, AUTOMATEWOO_MIN_PHP_VER );
			};
		}

		if ( ! self::is_woocommerce_version_ok() ) {
			$passed         = false;
			self::$errors[] = function () {
				/* translators: Plugin name. */
				$inactive_text = '<strong>' . sprintf( __( '%s is inactive.', 'automatewoo' ), __( 'AutomateWoo', 'automatewoo' ) ) . '</strong>';
				/* translators: %1$s inactive plugin text, %2$s minimum WooCommerce version */
				return sprintf( __( '%1$s The plugin requires WooCommerce version %2$s or newer.', 'automatewoo' ), $inactive_text, AUTOMATEWOO_MIN_WC_VER );
			};
		}

		if ( ! self::is_wp_version_ok() ) {
			$passed         = false;
			self::$errors[] = function () {
				/* translators: Plugin name. */
				$inactive_text = '<strong>' . sprintf( __( '%s is inactive.', 'automatewoo' ), __( 'AutomateWoo', 'automatewoo' ) ) . '</strong>';
				/* translators: %1$s inactive plugin text, %2$s minimum WordPress version */
				return sprintf( __( '%1$s The plugin requires WordPress version %2$s or newer.', 'automatewoo' ), $inactive_text, AUTOMATEWOO_MIN_WP_VER );
			};
		}

		return $passed;
	}

	/**
	 * Checks if the installed WooCommerce version is ok.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_version_ok() {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}
		if ( ! AUTOMATEWOO_MIN_WC_VER ) {
			return true;
		}
		return version_compare( WC()->version, AUTOMATEWOO_MIN_WC_VER, '>=' );
	}

	/**
	 * Checks if the installed WordPress version is ok.
	 *
	 * @return bool
	 */
	public static function is_wp_version_ok() {
		global $wp_version;
		if ( ! AUTOMATEWOO_MIN_WP_VER ) {
			return true;
		}
		return version_compare( $wp_version, AUTOMATEWOO_MIN_WP_VER, '>=' );
	}

	/**
	 * Displays any errors as admin notices.
	 */
	public static function admin_notices() {
		if ( empty( self::$errors ) ) {
			return;
		}

		foreach ( self::$errors as $error ) {
			echo '<div class="notice notice-error"><p>';
			echo wp_kses_post( $error() );
			echo '</p></div>';
		}
	}

	/**
	 * Declare compatibility for WooCommerce features.
	 *
	 * @since 5.5.23
	 */
	public static function declare_feature_compatibility() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
			FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__ );
		}
	}

	/**
	 * Set an option stating that AutomateWoo was activated.
	 *
	 * @since 5.8.5
	 *
	 * @return void
	 */
	public static function activation(): void {
		add_option( 'automatewoo_activated', true );
	}

	/**
	 * Trigger "activation" hook if "automatewoo_activated" option exists
	 *
	 * @since 5.8.5
	 *
	 * @return void
	 */
	public static function do_activation_action(): void {
		if ( get_option( 'automatewoo_activated' ) ) {
			do_action( 'automatewoo_activated' );
			delete_option( 'automatewoo_activated' );
		}
	}
}

AutomateWoo_Loader::init();

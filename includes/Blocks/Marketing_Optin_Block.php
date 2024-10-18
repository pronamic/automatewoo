<?php

namespace AutomateWoo\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use AutomateWoo\Options;
use AutomateWoo\Frontend;

/**
 * Class Marketing_Optin_Block
 *
 * Class for integrating marketing optin block with WooCommerce Checkout
 *
 * @since 5.6.0
 */
class Marketing_Optin_Block implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'automatewoo';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$script_path = WC()->plugin_path() . '/assets/js/build/marketing-optin-block-frontend.js';
		$script_url  = AW()->url( '/assets/js/build/marketing-optin-block-frontend.js' );

		$script_asset_path = WC()->plugin_path() . '/assets/js/build/marketing-optin-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_path ),
			);

		wp_register_script(
			'automatewoo-marketing-optin-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'automatewoo-marketing-optin-block-frontend',
			'automatewoo',
			AUTOMATEWOO_PATH . '/languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'automatewoo-marketing-optin-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Decide if the current customer already opted in or not.
	 *
	 * @return boolean
	 */
	public function is_customer_subscribed() {
		$customer = Frontend::get_current_customer();

		if ( $customer && $customer->get_is_subscribed() ) {
			return true; // customer already opted in
		}

		return false;
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data = array(
			'optinDefaultText' => Options::optin_checkbox_text(),
			'optinEnabled'     => Options::checkout_optin_enabled(),
			'alreadyOptedIn'   => $this->is_customer_subscribed(),
		);

		return $data;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return AUTOMATEWOO_VERSION;
	}
}

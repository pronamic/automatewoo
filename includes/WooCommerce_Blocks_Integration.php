<?php

namespace AutomateWoo;

use AutomateWoo\Blocks\Marketing_Optin_Block;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerce_Blocks_Integration
 *
 * @since 5.5.9
 * @package AutomateWoo
 */
class WooCommerce_Blocks_Integration {

	/**
	 * WooCommerce_Blocks_Integration constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'woocommerce_blocks_checkout_block_registration', [ $this, 'register_checkout_frontend_blocks' ] );
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_block', [ $this, 'add_attributes_to_frontend_blocks' ], 10, 1 );
		self::extend_store_api();
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {
		$asset_file_path = AUTOMATEWOO_PATH . '/assets/js/build/marketing-optin-block.asset.php';

		if ( file_exists( $asset_file_path ) && false === \WP_Block_Type_Registry::get_instance()->is_registered( 'automatewoo/marketing-optin' ) ) {
			register_block_type( AUTOMATEWOO_PATH . '/assets/js/marketing-optin-block' );
		}
	}

	/**
	 * Load blocks in frontend with Checkout.
	 *
	 * @param IntegrationRegistry $integration_registry
	 */
	public function register_checkout_frontend_blocks( $integration_registry ) {
		$marketing_optin_block = new Marketing_Optin_Block();

		if ( ! $integration_registry->is_registered( $marketing_optin_block->get_name() ) ) {
			$integration_registry->register( $marketing_optin_block );
		}
	}

	/**
	 * This allows dynamic (JS) blocks to access attributes in the frontend.
	 *
	 * @param string[] $allowed_blocks
	 */
	public function add_attributes_to_frontend_blocks( $allowed_blocks ) {
		$allowed_blocks[] = 'automatewoo/marketing-optin';
		return $allowed_blocks;
	}

	/**
	 * Add schema Store API to support posted data.
	 */
	public function extend_store_api() {

		$args = array(
			'endpoint'        => CheckoutSchema::IDENTIFIER,
			'namespace'       => 'automatewoo',
			'schema_callback' => function () {
				return array(
					'optin' => array(
						'description' => __( 'Subscribe to marketing opt-in.', 'automatewoo' ),
						'type'        => array( 'boolean', 'null' ),
						'context'     => array(),
						'arg_options' => array(
							'validate_callback' => function ( $value ) {
								if ( ! is_null( $value ) && ! is_bool( $value ) ) {
									return new \WP_Error( 'api-error', 'value of type ' . gettype( $value ) . ' was posted to the automatewoo optin callback' );
								}
								return true;
							},
							'sanitize_callback' => function ( $value ) {
								if ( is_bool( $value ) ) {
										return $value;
								}

								// Return a boolean when "null" is passed,
								// which is the only non-boolean value allowed.
								return false;
							},
						),
					),
				);
			},
		);

		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			woocommerce_store_api_register_endpoint_data( $args );
		} else {
			Package::container()->get( ExtendRestApi::class )->register_endpoint_data( $args );
		}
	}
}

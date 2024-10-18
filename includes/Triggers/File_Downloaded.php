<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Downloadable_File_Downloaded.
 *
 * @since 5.6.6
 * @package AutomateWoo
 */
class Trigger_File_Downloaded extends Trigger_Abstract_Downloadable_Content {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'File Downloaded', 'automatewoo' );
		$this->description = __( 'This trigger fires after a file is downloaded.', 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_download_product', array( $this, 'handle_file_downloaded' ), 10, 6 );
	}

	/**
	 * Handle file downloaded event.
	 *
	 * @param string $user_email  User Email address.
	 * @param string $order_key   Order key.
	 * @param int    $product_id  Product ID.
	 * @param int    $user_id     User ID.
	 * @param int    $download_id Download ID.
	 * @param int    $order_id    Order ID.
	 */
	public function handle_file_downloaded( $user_email, $order_key, $product_id, $user_id, $download_id, $order_id ) {
		$order    = wc_get_order( $order_id );
		$product  = wc_get_product( $product_id );
		$customer = Customer_Factory::get_by_order( $order );

		if ( ! $order || ! $product || ! $customer ) {
			return;
		}

		// Maybe run workflows for given downloadable file.
		$this->maybe_run_workflows( $download_id, $product, $order, $customer );
	}
}

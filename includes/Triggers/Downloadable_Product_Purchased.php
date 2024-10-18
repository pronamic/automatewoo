<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Downloadable_Product_Purchased.
 *
 * @since 5.6.6
 * @package AutomateWoo
 */
class Trigger_Downloadable_Product_Purchased extends Trigger_Abstract_Downloadable_Content {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::PRODUCT, DataTypes::ORDER, DataTypes::CUSTOMER ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Downloadable Product Purchased', 'automatewoo' );
		$this->description = __( 'This trigger fires after downloadable product is purchased.', 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$product = ( new Fields\Product() )
					->set_name( 'products' )
					->set_multiple( true )
					->set_title( __( 'Downloadable Products', 'automatewoo' ) )
					->set_description( __( 'Select downloadable products here to have this workflow trigger only for those specific products. Leave blank to run for all products.', 'automatewoo' ) )
					->set_allow_variations( true );

		$this->add_field( $product );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_grant_product_download_permissions', array( $this, 'handle_grant_product_download_permissions' ) );
	}

	/**
	 * Handle file downloaded event.
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_grant_product_download_permissions( $order_id ) {
		$order    = wc_get_order( $order_id );
		$customer = Customer_Factory::get_by_order( $order );

		if ( ! $order || ! $customer ) {
			return;
		}

		if ( count( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();

				if ( $product && $product->exists() && $product->is_downloadable() ) {
					foreach ( $this->get_workflows() as $workflow ) {
						$workflow_product_ids = Clean::ids( $workflow->get_trigger_option( 'products' ) );

						if ( ! empty( $workflow_product_ids ) && ! in_array( $product->get_id(), $workflow_product_ids, true ) ) {
							continue;
						}

						$workflow->maybe_run(
							[
								DataTypes::CUSTOMER => $customer,
								DataTypes::ORDER    => $order,
								DataTypes::PRODUCT  => $product,
							]
						);
					}
				}
			}
		}
	}
}

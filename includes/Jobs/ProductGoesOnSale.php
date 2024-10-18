<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Trigger_Wishlist_Item_Goes_On_Sale;

defined( 'ABSPATH' ) || exit;

/**
 * One time Recurring Job for checking if a product goes on sale.
 * This Job is required, for example, in the Trigger for Wishlist Items Goes on Sale
 *
 * @see Trigger_Wishlist_Item_Goes_On_Sale
 * @since 6.0.0
 */
class ProductGoesOnSale extends AbstractRecurringOneTimeActionSchedulerJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'products_goes_on_sale';
	}


	/**
	 * Updates automatewoo_products_last_on_sale option with the current product IDs on sale.
	 * If they are new products on sale, it triggers `automatewoo/products/gone_on_sale`
	 *
	 * @param array $item Not being used for this action.
	 */
	protected function process_item( array $item ) {
		$last_on_sale = get_option( 'automatewoo_products_last_on_sale' );
		$now_on_sale  = wc_get_product_ids_on_sale();
		update_option( 'automatewoo_products_last_on_sale', $now_on_sale, false );

		if ( ! is_array( $last_on_sale ) ) {
			$last_on_sale = [];
		}

		$diff = array_diff( $now_on_sale, $last_on_sale );

		if ( $diff ) {
			do_action( 'automatewoo/products/gone_on_sale', $diff );
		}
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for this action
	 */
	public function get_interval() {
		return JobService::FIFTEEN_MINUTE_INTERVAL;
	}
}

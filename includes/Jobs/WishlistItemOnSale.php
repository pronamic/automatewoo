<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Clean;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use AutomateWoo\Traits\ArrayValidator;
use AutomateWoo\Triggers;
use AutomateWoo\Wishlists;
use Exception;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * WishlistItemOnSale job class.
 *
 * Requires a 'products' arg which contains an array of product IDs that are recently on sale.
 *
 * @since 5.1.0
 */
class WishlistItemOnSale extends AbstractBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;
	use ArrayValidator;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wishlist_item_on_sale';
	}

	/**
	 * Get a new batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return int[]
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $args ) {
		return Wishlists::get_wishlist_ids(
			$this->get_batch_size(),
			$this->get_query_offset( $batch_number )
		);
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $item A single item from the get_batch() method. Expects a validated item.
	 * @param array $args The args for this instance of the job. Args are already validated.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_item( $item, array $args ) {
		$wishlist = Wishlists::get_wishlist( $item );
		if ( ! $wishlist ) {
			throw JobException::item_not_found();
		}

		$sale_product_ids = Clean::ids( $args['products'] );

		$trigger = Triggers::get( 'wishlist_item_goes_on_sale' );
		if ( ! $trigger ) {
			throw new RuntimeException( 'Wishlist on sale trigger not found.' );
		}

		$wishlist_items_on_sale = array_intersect( $wishlist->get_items(), $sale_product_ids );

		foreach ( $wishlist_items_on_sale as $product_id ) {
			$customer = Customer_Factory::get_by_user_id( $wishlist->get_user_id() );

			$trigger->maybe_run(
				[
					'customer' => $customer,
					'product'  => wc_get_product( $product_id ),
					'wishlist' => $wishlist,
				]
			);
		}
	}

	/**
	 * Validate the job args.
	 *
	 * @param array $args The args for this instance of the job.
	 *
	 * @throws InvalidArgument If args are invalid.
	 */
	protected function validate_args( array $args ) {
		if ( ! isset( $args['products'] ) ) {
			throw InvalidArgument::missing_required( 'products' );
		}

		$this->validate_is_non_empty_array( $args['products'] );
	}
}

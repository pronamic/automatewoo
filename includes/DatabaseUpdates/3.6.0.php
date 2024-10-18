<?php

/**
 * Update to 3.6.0
 *
 * Migrates single workflow unsubscribe data to unsubscribe to all workflows system.
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Customer_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database_Update_3_6_0
 *
 * @package AutomateWoo\DatabaseUpdates
 */
class Database_Update_3_6_0 extends AbstractDatabaseUpdate {

	/** @var string  */
	protected $version = '3.6.0';

	/**
	 * @return bool
	 */
	protected function process() {
		global $wpdb;

		$table = $wpdb->prefix . 'automatewoo_unsubscribes';
		$sql   = "SELECT * FROM `{$table}` GROUP BY `{$table}`.id LIMIT 15";
		// phpcs:disable WordPress.DB.PreparedSQL
		$results = $wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable

		if ( empty( $results ) ) {
			return true; // no more items to process, return complete
		}

		foreach ( $results as $unsubscribe ) {

			$customer = Customer_Factory::get( $unsubscribe['customer_id'] );

			if ( $customer ) { // customer might have been deleted

				if ( ! $customer->get_is_unsubscribed() ) {
					// set new unsub prop if not already set
					$customer->set_is_unsubscribed( true );
					$customer->set_date_unsubscribed( $unsubscribe['date'] );
					$customer->save();
				}
			}

			$wpdb->delete( $table, [ 'id' => $unsubscribe['id'] ] );

			++$this->items_processed;
		}

		return false;
	}
}

return new Database_Update_3_6_0();

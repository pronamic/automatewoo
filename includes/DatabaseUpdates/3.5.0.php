<?php

/**
 * Update to 3.5.0
 *
 * - change unsubscribe data to use customer ids
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Customer_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database_Update_3_5_0
 *
 * @package AutomateWoo\DatabaseUpdates
 */
class Database_Update_3_5_0 extends AbstractDatabaseUpdate {

	/** @var string */
	protected $version = '3.5.0';

	/**
	 * Runs immediately before a database update begins.
	 */
	protected function start() {
		parent::start();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$table = $wpdb->prefix . 'automatewoo_unsubscribes';

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		dbDelta(
			"CREATE TABLE {$table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) NULL,
			customer_id bigint(20) NOT NULL default 0,
			date datetime NULL,
			PRIMARY KEY  (id),
			KEY workflow_id (workflow_id),
			KEY customer_id (customer_id),
			KEY date (date)
			) {$collate};"
		);
	}

	/**
	 * @return bool
	 */
	protected function process() {
		global $wpdb;
		$table = $wpdb->prefix . 'automatewoo_unsubscribes';
		$sql   = "SELECT * FROM `{$table}` WHERE `{$table}`.`customer_id`=0 GROUP BY `{$table}`.id LIMIT 20";
		// phpcs:disable WordPress.DB.PreparedSQL
		$results = $wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable

		if ( empty( $results ) ) {
			// no more items to process return complete...
			return true;
		}

		foreach ( $results as $unsubscribe ) {

			if ( ! empty( $unsubscribe['user_id'] ) ) {
				$customer = Customer_Factory::get_by_user_id( $unsubscribe['user_id'] );
			} else {
				$customer = Customer_Factory::get_by_email( $unsubscribe['email'] );
			}

			if ( $customer ) {
				$wpdb->update(
					$table,
					[ 'customer_id' => $customer->get_id() ],
					[ 'id' => $unsubscribe['id'] ]
				);
			} else {
				// user might have been deleted
				$wpdb->delete( $table, [ 'id' => $unsubscribe['id'] ] );
				continue;
			}

			++$this->items_processed;
		}

		return false;
	}
}

return new Database_Update_3_5_0();

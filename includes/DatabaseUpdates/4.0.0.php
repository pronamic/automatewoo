<?php
// phpcs:ignoreFile
/**
 * Update to 4.0.0
 *
 * GDPR guest data
 * - clear IP address and browser agent
 * - mark guests whether have placed an order or not
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Guest_Query;

if ( ! defined( 'ABSPATH' ) ) exit;

class Database_Update_4_0_0 extends AbstractDatabaseUpdate {

	protected $version = '4.0.0';


	protected function start() {
		parent::start();

		global $wpdb;

		if ( ! get_option( 'automatewoo_optin_mode' ) ) {
			// existing stores stay with optout mode
			update_option( 'automatewoo_optin_mode', 'optout', false );
		}

		// drop IP column
		if ( $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}automatewoo_guests LIKE 'ip'" ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}automatewoo_guests DROP ip" );
		}
	}


	/**
	 * @return bool
	 */
	protected function process() {
		$limit = 5;

		$query = new Guest_Query();
		$query->set_limit( $limit );
		$query->where_version( $this->version, '<' );
		$results = $query->get_results();

		if ( empty( $results ) ) {
			return true; // no more items to process, return complete
		}

		foreach ( $results as $guest ) {
			$guest->set_version( $this->version );
			$guest->save();

			$guest->delete_meta( 'location_captured' );
			$guest->delete_meta( 'user_agent' );
			$order_id = $guest->recache_most_recent_order_id();

			if ( $order_id ) {
				// if the guest has an order we don't need the presubmit data, so delete it
				$guest->delete_presubmit_data();
			}

			$this->items_processed++;
		}

		return false;
	}


	/**
	 * @since 4.3.0
	 * @return bool|int
	 */
	public function get_items_to_process_count() {
		$query = new Guest_Query();
		$query->where_version( $this->version, '<' );
		return $query->get_count();
	}

}

return new Database_Update_4_0_0();

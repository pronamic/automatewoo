<?php
// phpcs:ignoreFile

namespace AutomateWoo\DatabaseTables;

use AutomateWoo\Database_Table;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Customers database table class.
 *
 * @since 3.0.0
 */
class Customers extends Database_Table {

	function __construct() {
		global $wpdb;

		$this->name = $wpdb->prefix . 'automatewoo_customers';
		$this->primary_key = 'id';
	}


	/**
	 * @return array
	 */
	function get_columns() {
		return [
			'id' => '%d',
			'user_id' => '%d',
			'guest_id' => '%d',
			'id_key' => '%s',
			'last_purchased' => '%s',
			'unsubscribed' => '%d',
			'unsubscribed_date' => '%s',
			'subscribed' => '%d',
			'subscribed_date' => '%s',
		];
	}


	/**
	 * @return string
	 */
	function get_install_query() {
		return "CREATE TABLE {$this->get_name()} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL default 0,
			guest_id bigint(20) NOT NULL default 0,
			id_key varchar(20) NOT NULL default '',
			last_purchased datetime NULL,
			unsubscribed int(1) NOT NULL DEFAULT 0,
			unsubscribed_date datetime NULL,
			subscribed int(1) NOT NULL DEFAULT 0,
			subscribed_date datetime NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY guest_id (guest_id),
			KEY id_key (id_key),
			KEY last_purchased (last_purchased),
			KEY unsubscribed (unsubscribed),
			KEY subscribed (subscribed)
			) {$this->get_collate()};";
	}

}

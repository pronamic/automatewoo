<?php
// phpcs:ignoreFile

namespace AutomateWoo\DatabaseTables;

use AutomateWoo\Database_Table;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Guests database table class.
 *
 * @since 2.8.2
 */
class Guests extends Database_Table {

	function __construct() {
		global $wpdb;

		$this->name = $wpdb->prefix . 'automatewoo_guests';
		$this->primary_key = 'id';
	}


	/**
	 * @return array
	 */
	function get_columns() {
		return [
			'id' => '%d',
			'email' => '%s',
			'tracking_key' => '%s',
			'created' => '%s',
			'last_active' => '%s',
			'language' => '%s',
			'most_recent_order' => '%d',
			'version' => '%s',
		];
	}


	/**
	 * @return string
	 */
	function get_install_query() {
		return "CREATE TABLE {$this->get_name()} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL default '',
			tracking_key varchar(32) NOT NULL default '',
			created datetime NULL,
			last_active datetime NULL,
			language varchar(10) NOT NULL default '',
			most_recent_order bigint(20) NOT NULL DEFAULT 0,
			version bigint(20) NOT NULL default 0,
			PRIMARY KEY  (id),
			KEY tracking_key (tracking_key),
			KEY email (email({$this->max_index_length})),
			KEY most_recent_order (most_recent_order),
			KEY version (version)
			) {$this->get_collate()};";
	}

}

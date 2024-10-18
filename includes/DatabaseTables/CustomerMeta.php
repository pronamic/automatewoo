<?php

namespace AutomateWoo\DatabaseTables;

use AutomateWoo\Database_Table;

defined( 'ABSPATH' ) || exit;

/**
 * CustomerMeta database table class.
 *
 * @since 4.6.0
 * @package AutomateWoo
 */
class CustomerMeta extends Database_Table {

	/**
	 * CustomerMeta constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->name             = $wpdb->prefix . 'automatewoo_customer_meta';
		$this->primary_key      = 'meta_id';
		$this->object_id_column = 'customer_id';
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'meta_id'     => '%d',
			'customer_id' => '%d',
			'meta_key'    => '%s',
			'meta_value'  => '%s',
		];
	}

	/**
	 * Get table install SQL.
	 *
	 * @return string
	 */
	public function get_install_query() {
		return "CREATE TABLE {$this->name} (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			customer_id bigint(20) NULL,
			meta_key varchar(255) NULL,
			meta_value longtext NULL,
			PRIMARY KEY  (meta_id),
			KEY customer_id (customer_id),
			KEY meta_key (meta_key({$this->max_index_length}))
			) {$this->get_collate()};";
	}
}

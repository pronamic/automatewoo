<?php

namespace AutomateWoo\DatabaseTables;

use AutomateWoo\Database_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carts database table class.
 *
 * @since 2.8.2
 */
class Carts extends Database_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->name        = $wpdb->prefix . 'automatewoo_abandoned_carts';
		$this->primary_key = 'id';
	}


	/**
	 * @return array
	 */
	public function get_columns() {
		return [
			'id'                           => '%d',
			'status'                       => '%s',
			'has_been_abandoned'           => '%d',
			'user_id'                      => '%d',
			'guest_id'                     => '%d',
			'last_modified'                => '%s',
			'created'                      => '%s',
			'items'                        => '%s',
			'coupons'                      => '%s',
			'fees'                         => '%s',
			'shipping_tax_total'           => '%d',
			'shipping_total'               => '%d',
			'shipping_total_is_calculated' => '%d',
			'total'                        => '%s',
			'token'                        => '%s',
			'currency'                     => '%s',
		];
	}


	/**
	 * @return string
	 */
	public function get_install_query() {
		return "CREATE TABLE {$this->get_name()} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			status varchar(100) NOT NULL default '',
			has_been_abandoned tinyint(1) NOT NULL default 0,
			user_id bigint(20) NOT NULL default 0,
			guest_id bigint(20) NOT NULL default 0,
			last_modified datetime NULL,
			created datetime NULL,
			items longtext NOT NULL,
			coupons longtext NOT NULL,
			fees longtext NOT NULL,
			shipping_tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			shipping_total_is_calculated tinyint(1) NOT NULL DEFAULT 0,
			total double DEFAULT 0 NOT NULL,
			token varchar(32) NOT NULL default '',
			currency varchar(8) NOT NULL default '',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY user_id (user_id),
			KEY guest_id (guest_id),
			KEY last_modified (last_modified),
			KEY created (created)
			) {$this->get_collate()};";
	}
}

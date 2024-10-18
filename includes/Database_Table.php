<?php

namespace AutomateWoo;

/**
 * @class Database_Table
 * @since 2.8.2
 */
abstract class Database_Table {

	/** @var string */
	protected $name;

	/** @var string */
	public $primary_key;

	/** @var string (only for meta tables) */
	public $object_id_column;

	/** @var int */
	public $max_index_length = 191;

	/**
	 * @return array
	 */
	abstract public function get_columns();

	/**
	 * Get SQL-escaped table name.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_sql( $this->name );
	}

	/**
	 * Get SQL-escaped object ID column.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_object_id_column() {
		return esc_sql( $this->object_id_column );
	}

	/**
	 * @return string
	 * @since 2.9.9
	 */
	public function get_install_query() {
		return '';
	}


	/**
	 * Install the database table
	 */
	public function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->get_install_query() );
	}


	/**
	 * @return string
	 */
	public function get_collate() {
		global $wpdb;
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		return $collate;
	}
}

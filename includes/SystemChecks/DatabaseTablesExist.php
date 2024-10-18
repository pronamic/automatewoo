<?php

namespace AutomateWoo\SystemChecks;

use AutomateWoo\Database_Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Class DatabaseTablesExist
 *
 * @package AutomateWoo\SystemChecks
 */
class DatabaseTablesExist extends AbstractSystemCheck {
	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->title         = __( 'Database Tables Installed', 'automatewoo' );
		$this->description   = __( 'Checks the AutomateWoo custom database tables have been installed.', 'automatewoo' );
		$this->high_priority = true;
	}

	/**
	 * Perform the check
	 */
	public function run() {
		global $wpdb;

		$expected_tables = Database_Tables::get_all();
		$missing_tables  = array();

		foreach ( $expected_tables as $table ) {
			$table_exists = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table->get_name() ) );

			if ( empty( $table_exists ) ) {
				$missing_tables[] = $table->get_name();
			}
		}

		if ( ! empty( $missing_tables ) ) {
			/* translators: %1$s list of missing database tables */
			return $this->error( sprintf( __( 'Tables not found: %1$s', 'automatewoo' ), implode( ', ', $missing_tables ) ) );
		}

		return $this->success();
	}
}

<?php

namespace AutomateWoo\CLI;

use AutomateWoo\Database_Tables;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command to add performance indexes to AutomateWoo log tables.
 *
 * Designed for controlled rollout on large stores where running
 * `dbDelta` during a plugin update could cause prolonged locks.
 *
 * The command is idempotent: it checks whether each index already
 * exists before issuing an ALTER TABLE statement.
 *
 * ## EXAMPLES
 *
 *     # Preview which indexes would be added
 *     wp automatewoo add-log-indexes --dry-run
 *
 *     # Add all missing indexes
 *     wp automatewoo add-log-indexes
 *
 * @since 6.3.2
 */
class AddLogIndexes {

	/**
	 * Get index definitions using live table names.
	 *
	 * @return array Format: [ table_name => [ index_name => column_definition ] ]
	 */
	private function get_index_definitions(): array {
		$logs_table_obj = Database_Tables::get( 'logs' );
		$meta_table_obj = Database_Tables::get( 'log-meta' );

		$logs_table       = $logs_table_obj->get_name();
		$meta_table       = $meta_table_obj->get_name();
		$max_index_length = $meta_table_obj->max_index_length;

		return [
			$logs_table => [
				'tracking_blocked_date'    => '(tracking_enabled, date)',
				'conversion_tracking_date' => '(conversion_tracking_enabled, date)',
			],
			$meta_table => [
				'log_id_meta_key' => "(log_id, meta_key({$max_index_length}))",
			],
		];
	}

	/**
	 * Add performance indexes to AutomateWoo log tables.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show which indexes would be added without making changes.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		global $wpdb;

		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$indexes = $this->get_index_definitions();

		$added   = 0;
		$skipped = 0;

		foreach ( $indexes as $table => $table_indexes ) {
			foreach ( $table_indexes as $index_name => $columns ) {
				if ( $this->index_exists( $table, $index_name ) ) {
					WP_CLI::log( sprintf( 'Index "%s" on %s already exists, skipping.', $index_name, $table ) );
					++$skipped;
					continue;
				}

				if ( $dry_run ) {
					WP_CLI::log( sprintf( '[DRY RUN] Would add index "%s" on %s %s', $index_name, $table, $columns ) );
					++$added;
					continue;
				}

				$sql = sprintf(
					'ALTER TABLE %s ADD INDEX %s %s',
					$table,
					$index_name,
					$columns
				);

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table and index names are not user input.
				$result = $wpdb->query( $sql );

				if ( false === $result ) {
					WP_CLI::warning( sprintf( 'Failed to add index "%s" on %s: %s', $index_name, $table, $wpdb->last_error ) );
				} else {
					WP_CLI::log( sprintf( 'Added index "%s" on %s %s', $index_name, $table, $columns ) );
					++$added;
				}
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( '%d index(es) would be added, %d already exist.', $added, $skipped ) );
		} else {
			WP_CLI::success( sprintf( '%d index(es) added, %d already existed.', $added, $skipped ) );
		}
	}

	/**
	 * Check if an index already exists on a table.
	 *
	 * @param string $table      Full table name.
	 * @param string $index_name Index name.
	 *
	 * @return bool
	 */
	private function index_exists( string $table, string $index_name ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is not user input.
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s',
				$table,
				$index_name
			)
		);

		return (int) $result > 0;
	}
}

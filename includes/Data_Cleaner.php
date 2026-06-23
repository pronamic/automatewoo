<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Removes all AutomateWoo data from the store.
 *
 * Single source of truth for what counts as "AutomateWoo data". Used both by
 * the immediate "Delete AutomateWoo data" tool (while the plugin is active) and
 * by uninstall.php (when the plugin is deleted with the opt-in setting enabled).
 *
 * This class must stay self-contained: uninstall.php loads it via the Composer
 * autoloader in a bare context where the rest of the plugin is NOT bootstrapped,
 * so it may only rely on $wpdb and WordPress core functions.
 *
 * @since 6.5.0
 */
class Data_Cleaner {

	/**
	 * WP-Cron hooks scheduled by AutomateWoo.
	 *
	 * @var string[]
	 */
	private static $cron_hooks = [
		'automatewoo_events_worker',
		'automatewoo_two_minute_worker',
		'automatewoo_five_minute_worker',
		'automatewoo_fifteen_minute_worker',
		'automatewoo_thirty_minute_worker',
		'automatewoo_hourly_worker',
		'automatewoo_four_hourly_worker',
		'automatewoo_daily_worker',
		'automatewoo_two_days_worker',
		'automatewoo_weekly_worker',
		'automatewoo_midnight',
	];

	/**
	 * Custom database tables (without the table prefix).
	 *
	 * @var string[]
	 */
	private static $tables = [
		'automatewoo_abandoned_carts',
		'automatewoo_events',
		'automatewoo_customer_meta',
		'automatewoo_customers',
		'automatewoo_guest_meta',
		'automatewoo_guests',
		'automatewoo_log_meta',
		'automatewoo_logs',
		'automatewoo_queue_meta',
		'automatewoo_queue',
	];

	/**
	 * User meta keys created by AutomateWoo.
	 *
	 * @var string[]
	 */
	private static $user_meta_keys = [
		'automatewoo_visitor_key',
		'automatewoo_email_preview_test_emails',
		'_automatewoo_customer_id',
		'_aw_order_count',
		'_aw_order_ids',
		'_aw_persistent_language',
		'_aw_user_registered',
	];

	/**
	 * Order meta keys created by AutomateWoo.
	 *
	 * @var string[]
	 */
	private static $order_meta_keys = [
		'_aw_conversion',
		'_aw_conversion_log',
		'_aw_is_paid',
		'_automatewoo_order_created',
		'automatewoo_cart_id',
	];

	/**
	 * Coupon meta keys created by AutomateWoo.
	 *
	 * @var string[]
	 */
	private static $coupon_meta_keys = [
		'_is_aw_coupon',
		'_is_aw_test_coupon',
		'_aw_workflow_id',
		'_aw_customer_id',
		'_aw_workflow_log_id',
	];

	/**
	 * Get a human-readable summary of the data that will be deleted.
	 *
	 * Used to show merchants real counts before they confirm deletion.
	 *
	 * @since 6.5.0
	 *
	 * @return array<string,int> Map of stable type key => row count, for types that have rows.
	 */
	public static function get_summary(): array {
		global $wpdb;

		$summary = [
			'workflows' => (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'aw_workflow' )
			),
			'logs'      => self::count_table( 'automatewoo_logs' ),
			'queue'     => self::count_table( 'automatewoo_queue' ),
			'carts'     => self::count_table( 'automatewoo_abandoned_carts' ),
			'customers' => self::count_table( 'automatewoo_customers' ),
			'guests'    => self::count_table( 'automatewoo_guests' ),
		];

		// Only surface types that actually have rows, so the merchant sees a meaningful list.
		return array_filter( $summary );
	}

	/**
	 * Permanently delete all AutomateWoo data.
	 *
	 * @since 6.5.0
	 *
	 * @return void
	 */
	public static function delete_all() {
		global $wpdb;

		self::delete_cron_events();
		self::delete_scheduled_actions();
		self::delete_workflows();
		self::delete_user_tag_terms();
		self::drop_tables();
		self::delete_meta();
		self::delete_options();
		self::delete_transients();

		// Flush the object cache so persistent caches don't resurrect deleted data.
		wp_cache_flush();
	}

	/**
	 * Count rows in a custom table, returning 0 if the table is missing.
	 *
	 * @param string $table Table name without prefix.
	 * @return int
	 */
	private static function count_table( string $table ): int {
		global $wpdb;

		$table_name = $wpdb->prefix . $table;

		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	/**
	 * Check whether a table exists.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @return bool
	 */
	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
	}

	/**
	 * Unschedule all WP-Cron events.
	 */
	private static function delete_cron_events() {
		foreach ( self::$cron_hooks as $hook ) {
			wp_unschedule_hook( $hook );
		}
	}

	/**
	 * Cancel all Action Scheduler actions in the 'automatewoo' group.
	 */
	private static function delete_scheduled_actions() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', [], 'automatewoo' );
		}
	}

	/**
	 * Delete all workflow posts (and their postmeta via WordPress core).
	 */
	private static function delete_workflows() {
		global $wpdb;

		$workflow_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'aw_workflow' )
		);

		foreach ( $workflow_ids as $workflow_id ) {
			wp_delete_post( absint( $workflow_id ), true );
		}
	}

	/**
	 * Delete user_tag taxonomy terms and their relationships.
	 */
	private static function delete_user_tag_terms() {
		global $wpdb;

		$term_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id, tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt WHERE tt.taxonomy = %s",
				'user_tag'
			)
		);

		foreach ( $term_rows as $term_row ) {
			$wpdb->delete( $wpdb->term_relationships, [ 'term_taxonomy_id' => absint( $term_row->term_taxonomy_id ) ] );
			$wpdb->delete( $wpdb->term_taxonomy, [ 'term_taxonomy_id' => absint( $term_row->term_taxonomy_id ) ] );

			// Only delete the term itself if no other taxonomy still uses it (shared terms).
			$still_used = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d",
					$term_row->term_id
				)
			);
			if ( ! $still_used ) {
				$wpdb->delete( $wpdb->terms, [ 'term_id' => absint( $term_row->term_id ) ] );
				$wpdb->delete( $wpdb->termmeta, [ 'term_id' => absint( $term_row->term_id ) ] );
			}
		}
	}

	/**
	 * Drop all custom database tables.
	 */
	private static function drop_tables() {
		global $wpdb;

		foreach ( self::$tables as $table ) {
			$table_name = $wpdb->prefix . $table;
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}
	}

	/**
	 * Delete user, order, coupon and booking meta created by AutomateWoo.
	 */
	private static function delete_meta() {
		global $wpdb;

		foreach ( self::$user_meta_keys as $meta_key ) {
			$wpdb->delete( $wpdb->usermeta, [ 'meta_key' => $meta_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		// WooCommerce may store order meta in wp_postmeta or in the HPOS orders_meta table.
		$hpos_meta_table = $wpdb->prefix . 'wc_orders_meta';
		$has_hpos_meta   = self::table_exists( $hpos_meta_table );

		foreach ( self::$order_meta_keys as $meta_key ) {
			$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $meta_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

			if ( $has_hpos_meta ) {
				$wpdb->delete( $hpos_meta_table, [ 'meta_key' => $meta_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			}
		}

		// Only remove coupon meta, not the coupon posts, to preserve order history integrity.
		foreach ( self::$coupon_meta_keys as $meta_key ) {
			$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $meta_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		// Booking meta.
		$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_automatewoo_is_created' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}

	/**
	 * Delete all AutomateWoo options (settings, internal state flags, cached data).
	 */
	private static function delete_options() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'automatewoo\_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_automatewoo\_%'" );

		// AutomateWoo only creates these two `aw_` options. A broad `aw_%` match could
		// catch options from other plugins using the same prefix.
		delete_option( 'aw_workers_last_run' );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aw\_wf\_preview\_data\_%'" );
	}

	/**
	 * Delete all transients created by AutomateWoo.
	 */
	private static function delete_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_aw\_cache\_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_automatewoo\_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_timeout\_aw\_cache\_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_timeout\_automatewoo\_%'" );
	}
}

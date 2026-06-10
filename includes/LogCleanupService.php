<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Shared service for deleting old workflow logs.
 *
 * Used by both the DeleteOldLogs scheduled job and the
 * `wp automatewoo delete-old-logs` CLI command so that cache
 * invalidation and deletion logic stay in one place.
 *
 * @since 6.3.2
 */
class LogCleanupService {

	/**
	 * Default batch size for log deletion.
	 *
	 * @var int
	 */
	const DEFAULT_BATCH_SIZE = 500;

	/**
	 * Get a batch of log IDs older than the given date.
	 *
	 * @since 6.3.2
	 *
	 * @param DateTime $before_date Logs with a date before this.
	 * @param int      $batch_size  Maximum rows to return.
	 *
	 * @return int[] Log IDs.
	 */
	public function get_log_ids_before_date( DateTime $before_date, int $batch_size = self::DEFAULT_BATCH_SIZE ): array {
		$query = new Log_Query();
		$query->where_date( $before_date, '<' );
		$query->set_ordering( 'date', 'ASC' );
		$query->set_limit( $batch_size );

		return $query->get_results_as_ids();
	}

	/**
	 * Delete a batch of logs older than the given date.
	 *
	 * Returns the number of log rows deleted in this batch.
	 *
	 * @since 6.3.2
	 *
	 * @param DateTime $before_date Delete logs with a date before this.
	 * @param int      $batch_size  Maximum rows to delete per call.
	 *
	 * @return int Number of log rows deleted.
	 */
	public function delete_logs_before_date( DateTime $before_date, int $batch_size = self::DEFAULT_BATCH_SIZE ): int {
		$log_ids = $this->get_log_ids_before_date( $before_date, $batch_size );

		if ( empty( $log_ids ) ) {
			return 0;
		}

		$workflow_ids = $this->get_workflow_ids_for_logs( $log_ids );

		$this->clean_conversion_meta_for_logs( $log_ids );

		foreach ( $log_ids as $log_id ) {
			$this->delete_log_by_id( $log_id );
		}

		$this->invalidate_times_run_caches( $workflow_ids );

		return count( $log_ids );
	}

	/**
	 * Clean up conversion tracking meta on WooCommerce orders for the given log IDs.
	 *
	 * When a log is deleted via Log::delete(), it removes `_aw_conversion` and
	 * `_aw_conversion_log` meta from associated orders. This method replicates
	 * that cleanup so the raw $wpdb deletes in delete_log_by_id() don't leave
	 * orphaned order meta.
	 *
	 * Resolves matching order IDs directly from the order-meta table rather
	 * than via `wc_get_orders`, because `wc_get_orders` only supports a
	 * `meta_query` argument on HPOS, and the single-value `meta_key` +
	 * `meta_value` form would require one query per log ID.
	 *
	 * @since 6.3.2
	 *
	 * @param int[] $log_ids Log IDs about to be deleted.
	 */
	private function clean_conversion_meta_for_logs( array $log_ids ): void {
		if ( empty( $log_ids ) ) {
			return;
		}

		global $wpdb;

		// Order meta stores log IDs as strings.
		$string_ids   = array_map( 'strval', $log_ids );
		$placeholders = implode( ',', array_fill( 0, count( $string_ids ), '%s' ) );

		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$meta_table = $wpdb->prefix . 'wc_orders_meta';
			$id_column  = 'order_id';
		} else {
			$meta_table = $wpdb->postmeta;
			$id_column  = 'post_id';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$order_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = '_aw_conversion_log' AND meta_value IN ({$placeholders})", $string_ids ) );

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$order->delete_meta_data( '_aw_conversion' );
			$order->delete_meta_data( '_aw_conversion_log' );
			$order->save();
		}
	}

	/**
	 * Delete a single log and its associated meta.
	 *
	 * Low-level helper. Does not clean conversion meta on related orders or
	 * invalidate per-workflow caches — see `delete_log_with_cleanup()` for
	 * a single-log path that performs the full cleanup.
	 *
	 * @since 6.3.2
	 *
	 * @param int $log_id The log ID to delete.
	 */
	public function delete_log_by_id( int $log_id ): void {
		global $wpdb;

		$logs_table = Database_Tables::get( 'logs' )->get_name();
		$meta_table = Database_Tables::get( 'log-meta' )->get_name();

		$wpdb->delete( $meta_table, [ 'log_id' => $log_id ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $logs_table, [ 'id' => $log_id ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Delete a single log and perform full cleanup: conversion order meta,
	 * log rows, and per-workflow times_run cache.
	 *
	 * Used by the `DeleteOldLogs` AS job which processes one log per action.
	 * The batch path (`delete_logs_before_date()`) does the equivalent work
	 * in bulk for a whole batch.
	 *
	 * @since 6.3.2
	 *
	 * @param int $log_id The log ID to delete.
	 */
	public function delete_log_with_cleanup( int $log_id ): void {
		$workflow_ids = $this->get_workflow_ids_for_logs( [ $log_id ] );
		$this->clean_conversion_meta_for_logs( [ $log_id ] );
		$this->delete_log_by_id( $log_id );
		$this->invalidate_times_run_caches( $workflow_ids );
	}

	/**
	 * Get the cutoff date based on the configured retention period.
	 *
	 * @since 6.3.2
	 *
	 * @param int $retention_months Number of months to retain logs.
	 *
	 * @return DateTime|false The cutoff date, or false if retention is disabled (0 months).
	 */
	public function get_retention_cutoff_date( int $retention_months ) {
		if ( $retention_months <= 0 ) {
			return false;
		}

		$cutoff = new DateTime();
		$cutoff->modify( "-{$retention_months} months" );

		return $cutoff;
	}

	/**
	 * Get distinct workflow IDs for a set of log IDs.
	 *
	 * @since 6.3.2
	 *
	 * @param int[] $log_ids Log IDs.
	 *
	 * @return int[] Workflow IDs.
	 */
	private function get_workflow_ids_for_logs( array $log_ids ): array {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return [];
		}

		$logs_table   = Database_Tables::get( 'logs' )->get_name();
		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT workflow_id FROM {$logs_table} WHERE id IN ({$placeholders})", $log_ids ) ) );
	}

	/**
	 * Invalidate per-workflow times_run transients.
	 *
	 * @since 6.3.2
	 *
	 * @param int[] $workflow_ids Workflow IDs whose caches should be flushed.
	 */
	private function invalidate_times_run_caches( array $workflow_ids ): void {
		foreach ( $workflow_ids as $workflow_id ) {
			Cache::delete_transient( 'times_run/workflow=' . $workflow_id );
		}
	}

	/**
	 * Invalidate caches that depend on log data.
	 *
	 * Should be called after logs are deleted so that workflow run-count
	 * queries and dashboard stats reflect the current state.
	 *
	 * @since 6.3.2
	 */
	public function invalidate_log_caches(): void {
		// Dashboard widget caches (charts, counters).
		Cache::flush_group( 'dashboard' );
	}
}

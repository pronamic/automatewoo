<?php

namespace AutomateWoo;

/**
 * Class Active_Triggers_Cache
 *
 * @since 4.8.0
 *
 * @package AutomateWoo
 */
final class Active_Triggers_Cache {

	/**
	 * Active triggers.
	 *
	 * @var array
	 */
	protected static $active_triggers;

	/**
	 * Init Active_Triggers_Cache
	 */
	public static function init() {
		// fires when a workflow is saved, trashed or deleted
		add_action( 'automatewoo/workflow/updated', [ __CLASS__, 'clear_active_triggers_cache' ] );
		add_action( 'automatewoo/workflow/deleted', [ __CLASS__, 'clear_active_triggers_cache' ] );

		// refresh cache when version changes
		add_action( 'automatewoo_version_changed', [ __CLASS__, 'clear_active_triggers_cache' ] );
	}

	/**
	 * Get an array of trigger names of triggers that are in use on the store.
	 *
	 * @return array
	 */
	public static function get_active_triggers() {
		if ( isset( self::$active_triggers ) ) {
			// Cache the active triggers in memory
			return self::$active_triggers;
		}

		$triggers = get_option( 'automatewoo_active_triggers' );

		if ( is_array( $triggers ) ) {
			$triggers = Clean::recursive( $triggers );
		} else {
			// Cache is missing
			$triggers = self::query_active_triggers();
			// Update option cache
			update_option( 'automatewoo_active_triggers', $triggers, true );
		}

		// Apply filter after getting from DB but before setting in memory.
		self::$active_triggers = apply_filters( 'automatewoo/active_triggers', $triggers );

		return self::$active_triggers;
	}

	/**
	 * Is a trigger actively in use?
	 *
	 * @param string $trigger_name
	 *
	 * @return bool
	 */
	public static function is_trigger_active( $trigger_name ) {
		return in_array( $trigger_name, self::get_active_triggers(), true );
	}

	/**
	 * Query active triggers.
	 *
	 * @return array
	 */
	protected static function query_active_triggers() {
		global $wpdb;

		$sql = "SELECT DISTINCT(meta.meta_value) FROM {$wpdb->posts} as posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			WHERE post_type = 'aw_workflow'
			AND post_status = 'publish'
			AND meta.meta_key = 'trigger_name'";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = Clean::recursive( $wpdb->get_col( $sql ) );
		// phpcs:enable

		return $results;
	}

	/**
	 * Clear active triggers cached values.
	 */
	public static function clear_active_triggers_cache() {
		// Note: Option will only update if value has changed
		update_option( 'automatewoo_active_triggers', '', true );
		self::$active_triggers = null;
	}
}

<?php

namespace AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use AutomateWoo\Admin\Analytics\Rest_API\Log_Stats_Store;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use DateTime;
use stdClass;
use WP_Error;

/**
 * REST API AW Email & SMS Tracking reports stats data store
 *
 * @version 5.6.8
 */
class Data_Store extends Log_Stats_Store {

	/**
	 * Table used to get tracking data details.
	 *
	 * @var string
	 */
	protected static $meta_table_name = 'automatewoo_log_meta';

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'email_tracking_stats';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'    => 'strval',
		'date_end'      => 'strval',
		'sent'          => 'intval',
		'opens'         => 'intval',
		'unique-clicks' => 'intval',
		'clicks'        => 'intval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	protected $context = 'email_tracking_stats';

	/**
	 * Assign report columns.
	 */
	protected function assign_report_columns() {
		global $wpdb;
		$meta_table_name = $wpdb->prefix . static::$meta_table_name;
		// API fields mapped to sql SELECT statements.
		// For intervals we need a non-aggregated query to access interval dates from `meta_value`.
		$this->report_columns = array(
			'sent'          => 'id as sent',
			'opens'         => "LOCATE( 's:4:\"type\";s:4:\"open\";', {$meta_table_name}.meta_value ) > 0 as opens",
			'unique-clicks' => "LOCATE( 's:4:\"type\";s:5:\"click\";', {$meta_table_name}.meta_value ) > 0 as 'unique-clicks'",
			'clicks'        => 'null as clicks',
		);
	}

	/**
	 * Returns a list of totals selected by the query_args.
	 *
	 * This is almost a copy of `selected_columns`, but for a given array instead of `$this->report_columns`,
	 * and without `implode`.
	 * Due to the fact we need access to `meta_value`s to select interval dates,
	 * we use an SQL query for individual intervals, but calculate totals as a PHP array.
	 *
	 * @param array $query_args User-supplied options.
	 * @param array $totals     Array of totals to be filtered.
	 * @return string
	 */
	protected function selected_totals( $query_args, $totals ) {
		if ( ! isset( $query_args['fields'] ) || ! is_array( $query_args['fields'] ) ) {
			return $totals;
		}

		return array_intersect_key( $totals, array_flip( $query_args['fields'] ) );
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @overwrites Log_Stats_Store::get_noncached_stats_data
	 * @see get_data
	 * @param array    $query_args              Query parameters.
	 * @param array    $params                  Query limit parameters.
	 * @param stdClass $data                    Reference to the data object to fill.
	 * @param int      $expected_interval_count Number of expected intervals.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_stats_data( $query_args, $params, &$data, $expected_interval_count ) {
		global $wpdb;

		$table_name = self::get_db_table_name();
		$this->initialize_queries();

		$selections = $this->selected_columns( $query_args );

		$this->update_sql_query_params( $query_args );
		$this->interval_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		// Get the total available log entries => "intervals", without `LIMIT` set.
		// Group them in SQL, not in PHP as we do below, as there is no individual meta values we need to fetch at this point.
		$this->interval_query->add_sql_clause( 'group_by', 'time_interval' );
		$db_intervals = $wpdb->get_col(
			$this->interval_query->get_query_statement() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$this->interval_query->clear_sql_clause( 'group_by' );

		$db_interval_count = count( $db_intervals );

		$intervals = array();
		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );

		$date_column_name = $this->date_column_name;
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'select', ", {$table_name}.{$date_column_name} AS datetime_anchor" );

		// Add meta property to manualy count non-unique clicks, and assign tracked event event dates.
		$meta_table_name = $wpdb->prefix . static::$meta_table_name;
		$this->interval_query->add_sql_clause( 'select', ", {$meta_table_name}.meta_value as meta_value" );
		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$logs = $wpdb->get_results(
			$this->interval_query->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$chosen_interval = $query_args['interval'];
		// Manually iterate over tracked events. Logic copied from legacy reports.
		$all_events         = array();
		$totals             = array(
			'sent'          => 0,
			'opens'         => 0,
			'unique-clicks' => 0,
			'clicks'        => 0,
		);
		$timezone           = $query_args['before']->getTimezone();
		$last_recorded_date = new DateTime( '0-0-0 0:0:0', $timezone );
		foreach ( $logs as $log ) {

			$click_recorded     = false;
			$log_tracked_events = maybe_unserialize( $log['meta_value'] );

			// Record the trackable workflow run => message sent.
			$event_date         = new DateTime( $log['datetime_anchor'], $timezone );
			$last_recorded_date = max( $last_recorded_date, $event_date );
			$time_interval      = TimeInterval::time_interval_id( $chosen_interval, $event_date );
			$all_events[]       = array(
				'time_interval' => $time_interval,
				'type'          => 'sent',
			);
			++$totals['sent'];

			// If there is no data, or data is in unexpected format ignore the entry and continue.
			if ( ! is_array( $log_tracked_events ) ) {
				continue;
			}

			// Record granular events.
			foreach ( $log_tracked_events as $event ) {

				if ( ! isset( $event['type'] ) ) {
					continue;
				}

				$event_date         = new DateTime( $event['date'], $timezone );
				$last_recorded_date = max( $last_recorded_date, $event_date );
				$time_interval      = TimeInterval::time_interval_id( $chosen_interval, $event_date );
				switch ( $event['type'] ) {
					case 'click':
						if ( ! $click_recorded ) {

							$all_events[]   = array(
								'time_interval' => $time_interval,
								'type'          => 'unique-clicks',
							);
							$click_recorded = true;
							++$totals['unique-clicks'];
						}
						$all_events[] = array(
							'time_interval' => $time_interval,
							'type'          => 'clicks',
						);
						++$totals['clicks'];
						break;

					case 'open':
						$all_events[] = array(
							'time_interval' => $time_interval,
							'type'          => 'opens',
						);
						++$totals['opens'];

						break;

				}
			}
		}

		// Group by interval.
		$grouped_intervals = array();
		foreach ( $all_events as $entry ) {
			$interval = $entry['time_interval'];
			if ( ! isset( $grouped_intervals[ $interval ] ) ) {
				$group = array(
					'time_interval'   => $interval,
					'sent'            => 0,
					'opens'           => 0,
					'unique-clicks'   => 0,
					'clicks'          => 0,
					'datetime_anchor' => $interval,
				);
			} else {
				$group = $grouped_intervals[ $interval ];
			}
			++$group[ $entry['type'] ];
			$group['datetime_anchor'] = max( $group['datetime_anchor'], $interval );

			$grouped_intervals[ $interval ] = $group;
		}
		// This is the result of what we would get for something like
		// `SELECT DATE_FORMAT(wp_automatewoo_log_meta.date, …) AS time_interval, SUM(…), … GROUP BY time_interval;`
		// If the tracked event date would be exposed in a separate column.
		$intervals = array_values( $grouped_intervals );

		if ( null === $intervals ) {
			return new WP_Error( 'automatewoo_email_tracking_stats_result_failed', __( 'Sorry, fetching email & SMS tracking data failed.', 'automatewoo' ) );
		}

		// `fill_in_missing_intervals` expect it to be an object.
		$data->totals    = (object) $this->selected_totals( $query_args, $totals );
		$data->intervals = $intervals;
		// We need to sort intervals, as we constructed them manually from not sorted tracked event dates.
		$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );

		// Update end dates, as tracked events, may happened after the queried log.date range.
		$end_datetime = $query_args['before'];
		if ( $query_args['before'] < $last_recorded_date ) {
			$end_datetime = TimeInterval::iterate( $last_recorded_date, $query_args['interval'] )->modify( '-1 ms' );
		}

		if ( $this->intervals_missing( $expected_interval_count, $db_interval_count, null, null, null, null, count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $end_datetime, $query_args['interval'], $data->intervals );

		}

		$this->create_interval_subtotals( $data->intervals );

		return $data;
	}

	/**
	 * Simplified versions of `TimeInterval::intervals_missing` as here we always have one page.
	 *
	 * @see get_limit_params
	 * @overwrites Automattic\WooCommerce\Admin\API\Reports\DataStore::intervals_missing
	 * @param int $expected_interval_count Expected number of intervals in total.
	 * @param int $db_interval_count       Total number of records for given period in the database.
	 * @param any $items_per_page          Number of items per page.
	 * @param any $page_no                 Page number.
	 * @param any $order                   asc or desc.
	 * @param any $order_by                Column by which the result will be sorted.
	 * @param int $intervals_count         Number of records for given (possibly shortened) time interval.
	 *
	 * @return bool
	 */
	public function intervals_missing( $expected_interval_count, $db_interval_count, $items_per_page, $page_no, $order, $order_by, $intervals_count ) {
		if ( $expected_interval_count <= $db_interval_count ) {
			return false;
		}
		return $intervals_count < $expected_interval_count;
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		parent::initialize_queries();

		global $wpdb;
		$logs_table_name = self::get_db_table_name();
		$meta_table_name = $wpdb->prefix . static::$meta_table_name;
		$join_clause     = "LEFT JOIN {$meta_table_name} ON ( {$logs_table_name}.id = {$meta_table_name}.log_id AND {$meta_table_name}.meta_key = 'tracking_data' )";
		// Consider only tracked runs with no blocked emails,
		// to match the legacy PHP reports.
		$tracking_where_clause = " AND {$logs_table_name}.tracking_enabled = 1 AND {$logs_table_name}.has_blocked_emails = 0";

		$this->interval_query->add_sql_clause( 'join', $join_clause );
		$this->interval_query->add_sql_clause( 'where', $tracking_where_clause );
		// We will have to group manually in PHP, as we need to access `date`s from `meta_value`s.
		$this->interval_query->clear_sql_clause( 'group_by' );
	}

	/**
	 * Overwrite limit properties.
	 * Always expect all results on a single page.
	 *
	 * Due to the event date being unrelated to log date, setting LIMITs in SQL does not make much sense.
	 * That's why we need to query everything in PHP anyway, so there is no point in paginating it,
	 * and paking the frontent request the full query n times.
	 *
	 * @overwrite Generic_Stats_Store::get_limit_params
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_limit_params( $query_args = array() ) {
		return [
			'offset'   => 0,
			// This is expected number of intervals.
			// However we can get more, as tracking events may happen after the requested dates.
			'per_page' => TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] ),
		];
	}
}

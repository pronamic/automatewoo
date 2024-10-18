<?php

namespace AutomateWoo\Admin\Analytics\Rest_API\Unsubscribers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Stats_Store;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use stdClass;
use WP_Error;

/**
 * REST API AW Unsubscribers report stats data store
 *
 * @version 5.6.8
 */
class Data_Store extends Generic_Stats_Store {
	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	protected static $table_name = 'automatewoo_customers';

	/**
	 * Column name to store date, instead of default `date_created`.
	 *
	 * @var string
	 */
	protected $date_column_name = 'unsubscribed_date';

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'unsubscribers_stats';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'    => 'strval',
		'date_end'      => 'strval',
		'unsubscribers' => 'intval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	protected $context = 'unsubscribers_stats';

	/**
	 * Assign report columns.
	 */
	protected function assign_report_columns() {
		// API fields mapped to sql SELECT statements.
		$this->report_columns = array(
			'unsubscribers' => 'COUNT(DISTINCT id) as unsubscribers',
		);
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
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
		$params     = $this->get_limit_params( $query_args );

		$this->update_sql_query_params( $query_args );
		$this->get_limit_sql_params( $query_args );
		$this->interval_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		$db_intervals = $wpdb->get_col(
			$this->interval_query->get_query_statement() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		$db_interval_count = count( $db_intervals );

		$intervals = array();
		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

		$totals = $wpdb->get_results(
			$this->total_query->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		if ( null === $totals ) {
			return new WP_Error( 'automatewoo_unsubscribers_stats_result_failed', __( 'Sorry, fetching unsubscribers data failed.', 'automatewoo' ) );
		}

		$date_column_name = $this->date_column_name;
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.{$date_column_name}) AS datetime_anchor" );
		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$intervals = $wpdb->get_results(
			$this->interval_query->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		if ( null === $intervals ) {
			return new WP_Error( 'automatewoo_unsubscribers_stats_result_failed', __( 'Sorry, fetching unsubscribers data failed.', 'automatewoo' ) );
		}

		$totals = (object) $this->cast_numbers( $totals[0] );

		$data->totals    = $totals;
		$data->intervals = $intervals;

		if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			$this->remove_extra_records( $data, $query_args['page'], $params['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );
		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}

		$this->create_interval_subtotals( $data->intervals );

		return $data;
	}
}

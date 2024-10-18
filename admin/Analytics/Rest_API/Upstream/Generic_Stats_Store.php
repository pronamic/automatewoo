<?php

namespace AutomateWoo\Admin\Analytics\Rest_API\Upstream;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\DataStore as Reports_Data_Store;
use stdClass;
use WP_Error;

/**
 * This is a generic class, to cover bits shared by all reports.
 * Discovered in https://github.com/woocommerce/automatewoo/pull/1226
 * and https://github.com/woocommerce/automatewoo/pull/1250
 * We may consider moving it eventually to `Automattic\WooCommerce\Admin\API\Reports\DataStore`,
 * so the other extensions and WC itself could make use of it, and get DRYier.
 * https://github.com/woocommerce/automatewoo/issues/1238
 *
 * @extends Reports_Data_Store
 * @version 5.6.8
 */
class Generic_Stats_Store extends Reports_Data_Store {

	/**
	 * Report columns.
	 *
	 * @var array
	 */
	protected $report_columns;

	/**
	 * Updates the database query with interval parameters.
	 *
	 * @param  array $query_args
	 * @return void
	 */
	protected function update_sql_query_params( $query_args ) {
		$table_name = self::get_db_table_name();
		$this->add_time_period_sql_params( $query_args, $table_name );
		$this->add_intervals_sql_params( $query_args, $table_name );
		$this->add_order_by_sql_params( $query_args );
		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 * Fetches it from cache or returns `get_noncached_stats_data` result.
	 *
	 * @see get_noncached_stats_data
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_data( $query_args ) {
		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'ASC',
			'orderby'  => 'date',
			'before'   => TimeInterval::default_before(),
			'after'    => TimeInterval::default_after(),
			'fields'   => '*',
			'interval' => 'week',
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		/*
		 * We need to get the cache key here because
		 * parent::update_intervals_sql_params() modifies $query_args.
		 */
		$cache_key = $this->get_cache_key( $query_args );
		$data      = $this->get_cached_data( $cache_key );

		if ( false === $data ) {
			$params                  = $this->get_limit_params( $query_args );
			$expected_interval_count = TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $params['per_page'] );

			// Default, empty data object.
			$data = (object) array(
				'totals'    => null,
				'intervals' => [],
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);
			// If the requested page is out off range, return the deault empty object.
			if ( $query_args['page'] >= 1 && $query_args['page'] <= $total_pages ) {
				// Fetch the actual data.
				$data = $this->get_noncached_stats_data( $query_args, $params, $data, $expected_interval_count );
			}
			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
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
		/* translators: %s: Method name */
		return new \WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'automatewoo' ), __METHOD__ ), array( 'status' => 405 ) );
	}


	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		unset( $this->subquery );
		$table_name = self::get_db_table_name();

		$this->total_query = new SqlQuery( $this->context . '_total' );
		$this->total_query->add_sql_clause( 'from', $table_name );

		$this->interval_query = new SqlQuery( $this->context . '_interval' );
		$this->interval_query->add_sql_clause( 'from', $table_name );
		$this->interval_query->add_sql_clause( 'group_by', 'time_interval' );
	}
}

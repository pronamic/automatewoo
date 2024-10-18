<?php

namespace AutomateWoo\Admin\Analytics\Rest_API\Conversions\Stats;

use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Stats_Store;
use AutomateWoo\HPOS_Helper;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use stdClass;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API AW Conversions report stats data store
 *
 * @since 5.6.9
 */
class Store extends Generic_Stats_Store {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_order_stats';

	/**
	 * Table used to get conversion data details.
	 *
	 * @var object `(name:string , id_column: string)`
	 */
	protected $meta_table = null;

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'conversions_stats';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'   => 'strval',
		'date_end'     => 'strval',
		'orders_count' => 'intval',
		'total_sales'  => 'floatval',
		'net_revenue'  => 'floatval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	protected $context = 'conversions_stats';

	/**
	 * Sets `meta_table` details according to the HPOS being enabled or not.
	 */
	public function __construct() {
		global $wpdb;
		parent::__construct();

		// Set meta table details according to the HPOS being enabled or not.
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$this->meta_table = (object) array(
				'name'      => $wpdb->prefix . 'wc_orders_meta',
				'id_column' => 'order_id',
			);
		} else {
			$this->meta_table = (object) array(
				'name'      => $wpdb->prefix . 'postmeta',
				'id_column' => 'post_id',
			);
		}
	}

	/**
	 * Assign report columns.
	 */
	protected function assign_report_columns() {
		$table_name = self::get_db_table_name();

		$this->report_columns = array(
			'orders_count' => "SUM( CASE WHEN {$table_name}.parent_id = 0 THEN 1 ELSE 0 END ) as orders_count",
			'total_sales'  => "SUM({$table_name}.total_sales) AS total_sales",
			'net_revenue'  => "SUM({$table_name}.net_total) AS net_revenue",
		);
	}

	/**
	 * Updates the totals and intervals database queries with `workflows` parameter.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function update_sql_query_params( $query_args ) {
		parent::update_sql_query_params( $query_args );

		// Filter selected workflows.
		if ( ! empty( $query_args['workflows'] ) ) {
			$included_workflows     = implode( ',', wp_parse_id_list( $query_args['workflows'] ) );
			$workflows_where_clause = " AND {$this->meta_table->name}.meta_value IN ({$included_workflows})";

			$this->total_query->add_sql_clause( 'where', $workflows_where_clause );
			$this->interval_query->add_sql_clause( 'where', $workflows_where_clause );
		}
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
		// Set up where, and where_time clauses.
		$this->update_sql_query_params( $query_args );

		$selections = $this->selected_columns( $query_args );
		$where_time = $this->get_sql_clause( 'where_time' );
		$params     = $this->get_limit_sql_params( $query_args );

		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'where_time', $where_time );
		$totals = $wpdb->get_results(
			$this->total_query->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		if ( null === $totals ) {
			return new WP_Error( 'automatewoo_conversions_result_failed', __( 'Sorry, fetching conversions data failed.', 'automatewoo' ) );
		}

		$totals = (object) $this->cast_numbers( $totals[0] );

		$this->interval_query->add_sql_clause( 'where_time', $where_time );
		$db_intervals = $wpdb->get_col(
			$this->interval_query->get_query_statement() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		$db_interval_count = count( $db_intervals );

		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.date_created) AS datetime_anchor" );
		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}
		$intervals = $wpdb->get_results(
			$this->interval_query->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		); // phpcs:ignore cache ok, DB call ok, unprepared SQL ok.

		if ( null === $intervals ) {
			return new WP_Error( 'automatewoo_conversions_result_failed', __( 'Sorry, fetching conversions data failed.', 'automatewoo' ) );
		}

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

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		parent::initialize_queries();

		$order_stats_table_name = self::get_db_table_name();
		$join_clause            = "JOIN {$this->meta_table->name} ON ( {$order_stats_table_name}.order_id = {$this->meta_table->name}.{$this->meta_table->id_column} AND {$this->meta_table->name}.meta_key = '_aw_conversion' )";

		// We hardcode paid statuses only.
		// This is to match the legacy Reports behavior,
		// but is inconsistent with set of statuses used by WC Core analytics.
		// https://github.com/woocommerce/automatewoo/issues/1266
		$allowed_statuses    = array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() );
		$status_where_clause = " AND {$order_stats_table_name}.status IN ( '" . implode( "','", $allowed_statuses ) . "' )";

		$this->total_query->add_sql_clause( 'join', $join_clause );
		$this->total_query->add_sql_clause( 'where', $status_where_clause );

		$this->interval_query->add_sql_clause( 'join', $join_clause );
		$this->interval_query->add_sql_clause( 'where', $status_where_clause );
	}
}

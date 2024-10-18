<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Conversions;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\HPOS_Helper;
use AutomateWoo\Log_Factory;
use AutomateWoo\Workflows\Factory as WorkflowFactory;
use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Datastore for the Conversions List.
 *
 * @since 5.7.0
 */
class Store extends ReportsDataStore implements DataStoreInterface {

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
	protected $cache_key = 'conversions_list';

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	protected $context = 'conversions_list';

	/**
	 * Report columns.
	 *
	 * @var array
	 */
	protected $report_columns;

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'order_id'         => 'intval',
		'parent_id'        => 'intval',
		'date_created'     => 'strval',
		'date_created_gmt' => 'strval',
		'customer_id'      => 'intval',
		'total_sales'      => 'floatval',
		'workflow_id'      => 'intval',
		'conversion_id'    => 'intval',
	);

	/**
	 * Constructor to intialize the table and date column.
	 */
	public function __construct() {
		global $wpdb;

		// Set meta table details according to HPOS being enabled or not.
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$this->meta_table = (object) array(
				'name'      => "{$wpdb->prefix}wc_orders_meta",
				'id_column' => 'order_id',
			);
		} else {
			$this->meta_table = (object) array(
				'name'      => "{$wpdb->prefix}postmeta",
				'id_column' => 'post_id',
			);
		}

		// Dynamically sets the date column name based on configuration.
		$this->date_column_name = get_option( 'woocommerce_date_type', 'date_created' );
		parent::__construct();
	}

	/**
	 * Assign report columns once full table name has been assigned.
	 */
	protected function assign_report_columns() {
		$table_name = self::get_db_table_name();
		// Avoid ambigious columns in SQL query.
		$this->report_columns = array(
			'order_id'         => "DISTINCT {$table_name}.order_id",
			// Add 'date' field based on date type setting.
			'date'             => "{$table_name}.{$this->date_column_name} AS date",
			'date_created'     => "{$table_name}.date_created",
			'date_created_gmt' => "{$table_name}.date_created_gmt",
			'customer_id'      => "{$table_name}.customer_id",
			'total_sales'      => "{$table_name}.total_sales",
			'workflow_id'      => 'aw_workflow.meta_value AS workflow_id',
			'conversion_id'    => 'aw_conversion.meta_value AS conversion_id',
		);
	}

	/**
	 * Updates the database query with parameters used for the conversion list.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function add_sql_query_params( $query_args ) {
		global $wpdb;
		$order_stats_table_name = self::get_db_table_name();

		$this->add_time_period_sql_params( $query_args, $order_stats_table_name );
		$this->get_limit_sql_params( $query_args );
		$this->add_order_by_sql_params( $query_args );

		$this->subquery->add_sql_clause( 'join', "JOIN {$this->meta_table->name} aw_workflow ON ( {$order_stats_table_name}.order_id = aw_workflow.{$this->meta_table->id_column} AND aw_workflow.meta_key = '_aw_conversion' )" );
		$this->subquery->add_sql_clause( 'join', "JOIN {$this->meta_table->name} aw_conversion ON ( {$order_stats_table_name}.order_id = aw_conversion.{$this->meta_table->id_column} AND aw_conversion.meta_key = '_aw_conversion_log' )" );

		// We hardcode paid statuses only.
		// This is to match the legacy Reports behavior,
		// but is inconsistent with set of statuses used by WC Core analytics.
		// https://github.com/woocommerce/automatewoo/issues/1266
		$query_args['status_is'] = wc_get_is_paid_statuses();
		$this->subquery->add_sql_clause( 'where', 'AND ' . $this->get_status_subquery( $query_args ) );
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'      => get_option( 'posts_per_page' ),
			'page'          => 1,
			'order'         => 'DESC',
			'orderby'       => $this->date_column_name,
			'before'        => TimeInterval::default_before(),
			'after'         => TimeInterval::default_after(),
			'fields'        => '*',
			'extended_info' => false,
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
			$this->initialize_queries();

			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections = $this->selected_columns( $query_args );
			$params     = $this->get_limit_params( $query_args );
			$this->add_sql_query_params( $query_args );
			/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT( DISTINCT tt.order_id ) FROM (
					{$this->subquery->get_query_statement()}
				) AS tt"
			);
			/* phpcs:enable */

			if ( 0 === $params['per_page'] ) {
				$total_pages = 0;
			} else {
				$total_pages = (int) ceil( $db_records_count / $params['per_page'] );
			}
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				$data = (object) array(
					'data'    => array(),
					'total'   => $db_records_count,
					'pages'   => $total_pages,
					'page_no' => 0,
				);
				return $data;
			}

			$this->subquery->clear_sql_clause( 'select' );
			$this->subquery->add_sql_clause( 'select', $selections );
			$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
			$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
			/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared */
			$orders_data = $wpdb->get_results(
				$this->subquery->get_query_statement(),
				ARRAY_A
			);
			/* phpcs:enable */

			if ( null === $orders_data ) {
				return $data;
			}

			if ( $query_args['extended_info'] ) {
				$this->include_extended_info( $orders_data, $query_args );
			}

			$orders_data = array_map( array( $this, 'cast_numbers' ), $orders_data );
			$data        = (object) array(
				'data'    => $orders_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			$this->set_cached_data( $cache_key, $data );
		}
		return $data;
	}

	/**
	 * Enriches the order data.
	 *
	 * @param array $orders_data Orders data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$orders_data, $query_args ) {
		$customers   = $this->get_customers_by_orders( $orders_data );
		$workflows   = $this->get_workflows_by_orders( $orders_data );
		$conversions = $this->get_conversions_by_orders( $orders_data );
		$defaults    = array(
			'customer'   => array(),
			'workflow'   => array(),
			'conversion' => array(),
		);

		foreach ( $orders_data as $key => $order_data ) {
			$orders_data[ $key ]['extended_info'] = $defaults;
			if ( $order_data['customer_id'] && isset( $customers[ $order_data['customer_id'] ] ) ) {
				$orders_data[ $key ]['extended_info']['customer'] = $customers[ $order_data['customer_id'] ];
			}
			if ( $order_data['workflow_id'] && isset( $workflows[ $order_data['workflow_id'] ] ) ) {
				$orders_data[ $key ]['extended_info']['workflow'] = $workflows[ $order_data['workflow_id'] ];
			}
			if ( $order_data['conversion_id'] && isset( $conversions[ $order_data['conversion_id'] ] ) ) {
				$orders_data[ $key ]['extended_info']['conversion'] = $conversions[ $order_data['conversion_id'] ];
			}
		}
	}

	/**
	 * Get customer data from Order data.
	 *
	 * @param array $orders Array of orders data.
	 * @return array
	 */
	protected function get_customers_by_orders( $orders ) {
		global $wpdb;

		$customer_lookup_table = "{$wpdb->prefix}wc_customer_lookup";
		$customer_ids          = array_unique( array_column( $orders, 'customer_id' ) );

		if ( empty( $customer_ids ) ) {
			return array();
		}

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
		$customer_ids = implode( ',', $customer_ids );
		$customers    = $wpdb->get_results(
			"SELECT * FROM {$customer_lookup_table} WHERE customer_id IN ({$customer_ids})",
			ARRAY_A
		);
		/* phpcs:enable */

		// Return customer array reindexed by customer_id.
		return array_column( $customers, null, 'customer_id' );
	}

	/**
	 * Get workflow data from Order data.
	 *
	 * @param array $orders Array of orders data.
	 * @return array
	 */
	protected function get_workflows_by_orders( $orders ) {
		$workflow_ids = array_unique( array_column( $orders, 'workflow_id' ) );

		if ( empty( $workflow_ids ) ) {
			return array();
		}

		$workflows = array();
		foreach ( $workflow_ids as $workflow_id ) {
			$workflow = WorkflowFactory::get( $workflow_id );

			if ( ! $workflow || ! $workflow->exists ) {
				continue;
			}

			$workflows[ $workflow_id ] = array(
				'name' => $workflow->get_title(),
			);
		}

		return $workflows;
	}

	/**
	 * Get conversion data from Order data.
	 *
	 * @param array $orders Array of orders data.
	 * @return array
	 */
	protected function get_conversions_by_orders( $orders ) {
		$conversion_ids = array_unique( array_column( $orders, 'conversion_id' ) );

		if ( empty( $conversion_ids ) ) {
			return array();
		}

		$conversions = array();
		foreach ( $conversion_ids as $conversion_id ) {
			$conversion  = Log_Factory::get( $conversion_id );
			$date_opened = $conversion ? $conversion->get_date_opened() : false;

			if ( ! $conversion || ! $date_opened ) {
				continue;
			}

			$conversions[ $conversion_id ] = array(
				'date_opened' => $date_opened->format( TimeInterval::$sql_datetime_format ),
			);
		}

		return $conversions;
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @param string $order_by Order by option requeste by user.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return $this->date_column_name;
		}

		return $order_by;
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'select', self::get_db_table_name() . '.order_id' );
		$this->subquery->add_sql_clause( 'from', self::get_db_table_name() );
	}
}

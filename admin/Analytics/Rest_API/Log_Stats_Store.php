<?php

namespace AutomateWoo\Admin\Analytics\Rest_API;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Stats_Store;

/**
 * Base class for log-related REST API reports stats data stores.
 *
 * @version 5.6.8
 */
abstract class Log_Stats_Store extends Generic_Stats_Store {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	protected static $table_name = 'automatewoo_logs';

	/**
	 * Column name to store date, instead of default `date_created`.
	 *
	 * @var string
	 */
	protected $date_column_name = 'date';

	/**
	 * Updates the database query with parameters used for workflow runs report: workflow_id.
	 *
	 * @param  array $query_args
	 * @return void
	 */
	protected function update_sql_query_params( $query_args ) {
		parent::update_sql_query_params( $query_args );

		// Filter selected workflows.
		if ( ! empty( $query_args['workflows'] ) ) {
			$logs_table_name        = self::get_db_table_name();
			$included_products      = implode( ',', wp_parse_id_list( $query_args['workflows'] ) );
			$workflows_where_clause = " AND {$logs_table_name}.workflow_id IN ({$included_products})";

			$this->total_query->add_sql_clause( 'where', $workflows_where_clause );
			$this->interval_query->add_sql_clause( 'where', $workflows_where_clause );
		}
	}
}

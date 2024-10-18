<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Conversions\Stats;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Log_Stats_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;

/**
 * REST API Conversions Report stats controller class.
 *
 * @extends Log_Stats_Controller
 * @since 5.6.9
 */
class Controller extends Log_Stats_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/conversions/stats';

	/**
	 * Forwards a Conversions Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-conversions-stats' );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array(
			'total_sales'  => array(
				'description' => __( 'Converted order value.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'net_revenue'  => array(
				'description' => __( 'Converted net sales.', 'automatewoo' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'orders_count' => array(
				'title'       => __( 'Orders', 'automatewoo' ),
				'description' => __( 'Number of orders', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
			),
		);
	}
	/**
	 * Get the Conversions Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_conversions_stats';

		return $this->add_additional_fields_schema( $schema );
	}
}

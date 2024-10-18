<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Unsubscribers;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Stats_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;

/**
 * REST API Unsubscribers Report stats controller class.
 *
 * @extends Generic_Stats_Controller
 */
class Stats_Controller extends Generic_Stats_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/unsubscribers/stats';

	/**
	 * Forwards a Unsubscribers Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-unsubscribers-stats' );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array(
			'unsubscribers' => array(
				'description' => __( 'Number of unsubscribers.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
	/**
	 * Get the Unsubscribers Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_unsubscribers_stats';

		return $this->add_additional_fields_schema( $schema );
	}
}

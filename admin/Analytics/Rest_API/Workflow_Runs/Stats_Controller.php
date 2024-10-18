<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Workflow_Runs;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Log_Stats_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;

/**
 * REST API Reports workflows stats controller class.
 *
 * @extends Log_Stats_Controller
 */
class Stats_Controller extends Log_Stats_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/workflow-runs/stats';

	/**
	 * Forwards a Workflow Runs Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-workflow-runs-stats' );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * To be extended by specific report properites.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array(
			'runs' => array(
				'description' => __( 'Number of workflow runs.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
	/**
	 * Get the Workflow Runs Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_workflow_runs_stats';

		return $this->add_additional_fields_schema( $schema );
	}
}

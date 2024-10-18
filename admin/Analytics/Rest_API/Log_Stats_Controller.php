<?php
namespace AutomateWoo\Admin\Analytics\Rest_API;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Stats_Controller;
use WP_REST_Request;

/**
 * This is a bit more AW Logs specific class, to cover bits shared by reports.
 * This class separates AutomateWoo specific, workflow-related code
 * from the `Generic_Stats_Controller`, to make the latter one ready to be upstreamed.
 *
 * @extends Generic_Stats_Controller
 */
class Log_Stats_Controller extends Generic_Stats_Controller {
	/**
	 * Get the query params for collections.
	 * Extend params with `workflows` to be able to specify the ids to filter,
	 * and with `segmentby` to allow segmenting by `workflow_id`.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['workflows'] = array(
			'description'       => __( 'Limit result set to workflows assigned specific workflow IDs.', 'automatewoo' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['segmentby'] = array(
			'description'       => __( 'Segment the response by additional constraint.', 'automatewoo' ),
			'type'              => 'string',
			'enum'              => array(
				'workflow_id',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array();
	}
}

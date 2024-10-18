<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Upstream;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericController as WCGenericController;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This is a generic class, to cover bits shared by all reports.
 * Discovered in https://github.com/woocommerce/automatewoo/pull/1226#pullrequestreview-1210449142
 * We may consider moving it eventually to `Automattic\WooCommerce\Admin\API\Reports\GenericController`,
 * so the other extensions and WC itself could make use of it, and get DRYier.
 * https://github.com/woocommerce/automatewoo/issues/1238
 *
 * @extends WCGenericController
 */
class Generic_Controller extends WCGenericController {

	/**
	 * Forwards a Query constructor,
	 * to be able to customize Query class for a specific report.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, $this->rest_base );
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * `WP_REST_Request` does not expose a method to return all params covering defaults,
	 * as it does for `$request['param']` accessor.
	 * Therefore, we re-implement defaults resolution.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return array Simplified array of params.
	 */
	public function prepare_reports_query( $request ) {
		$args = wp_parse_args(
			array_intersect_key(
				$request->get_query_params(),
				$this->get_collection_params()
			),
			$request->get_default_params()
		);

		return $args;
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param stdClass        $report  Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = get_object_vars( $report );

		return parent::prepare_item_for_response( $data, $request );
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
		return array();
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * To be extended for each report.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->add_additional_fields_schema( array() );
	}
}

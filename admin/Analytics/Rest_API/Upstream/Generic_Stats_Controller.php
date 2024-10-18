<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Upstream;

defined( 'ABSPATH' ) || exit;


use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController as WCGenericStatsController;
use Automattic\WooCommerce\Admin\API\Reports\ParameterException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This is a generic class, to cover bits shared by all reports.
 * Discovered in https://github.com/woocommerce/automatewoo/pull/1226#pullrequestreview-1210449142
 * We may consider moving it eventually to `Automattic\WooCommerce\Admin\API\Reports\GenericStatsController`,
 * so the other extensions and WC itself could make use of it, and get DRYier.
 * https://github.com/woocommerce/automatewoo/issues/1238
 */
abstract class Generic_Stats_Controller extends WCGenericStatsController {

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$query_args = $this->prepare_reports_query( $request );
		$query      = $this->construct_query( $query_args );
		try {
			$report_data = $query->get_data();
		} catch ( ParameterException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$out_data = array(
			'totals'    => $report_data->totals ? get_object_vars( $report_data->totals ) : null,
			'intervals' => array(),
		);

		foreach ( $report_data->intervals as $interval_data ) {
			$item                    = $this->prepare_item_for_response( (object) $interval_data, $request );
			$out_data['intervals'][] = $this->prepare_response_for_collection( $item );
		}

		return $this->add_pagination_headers(
			$request,
			$out_data,
			(int) $report_data->total,
			(int) $report_data->page_no,
			(int) $report_data->pages
		);
	}

	/**
	 * Get the query params for collections.
	 *
	 * You may consider extending it with `segmentby`, with:
	 * ```
	 * $params = parent::get_collection_params();
	 * $params['segmentby'] = array(
	 *     'description'       => __( 'Segment the response by additional constraint.', 'my-extension' ),
	 *     'type'              => 'string',
	 *     'enum'              => array(
	 *         'property_name',
	 *     ),
	 *     'validate_callback' => 'rest_validate_request_arg',
	 * );
	 * ```
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['fields'] = array(
			'description'       => __( 'Limit stats fields to the specified items.', 'automatewoo' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
			),
		);

		return $params;
	}

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
}

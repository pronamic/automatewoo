<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Conversions;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;
use Automattic\WooCommerce\Admin\API\Reports\ParameterException;


/**
 * REST API Conversions Report list controller class.
 *
 * @since 5.7.0
 */
class Controller extends Generic_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/conversions';

	/**
	 * Forwards a Conversions Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-conversions-list' );
	}

	/**
	 * Get the order number for an order. If no filter is present for `woocommerce_order_number`, we can just return the ID.
	 * Returns the parent order number if the order is actually a refund.
	 *
	 * @param  int $order_id Order ID.
	 * @return string|null
	 */
	protected function get_order_number( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order && ! $order instanceof \WC_Order_Refund ) {
			return null;
		}

		if ( 'shop_order_refund' === $order->get_type() ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		if ( ! has_filter( 'woocommerce_order_number' ) ) {
			return (string) $order->get_id();
		}

		return $order->get_order_number();
	}

	/**
	 * Get the order total with the related currency formatting.
	 * Returns the parent order total if the order is actually a refund.
	 *
	 * @param  int $order_id Order ID.
	 * @return string|null
	 */
	protected function get_total_formatted( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order && ! $order instanceof \WC_Order_Refund ) {
			return null;
		}

		if ( 'shop_order_refund' === $order->get_type() ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		return wp_strip_all_tags( html_entity_decode( $order->get_formatted_order_total() ), true );
	}


	/**
	 * Get all reports.
	 *
	 * @param \WP_REST_Request $request Request data.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$query_args = $this->prepare_reports_query( $request );
		$query      = $this->construct_query( $query_args );
		try {
			$report_data = $query->get_data();
		} catch ( ParameterException $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$data = array();

		foreach ( $report_data->data as $orders_data ) {
			$orders_data['order_number']    = $this->get_order_number( $orders_data['order_id'] );
			$orders_data['total_formatted'] = $this->get_total_formatted( $orders_data['order_id'] );
			$item                           = $this->prepare_item_for_response( (object) $orders_data, $request );
			$data[]                         = $this->prepare_response_for_collection( $item );
		}

		return $this->add_pagination_headers(
			$request,
			$data,
			(int) $report_data->total,
			(int) $report_data->page_no,
			(int) $report_data->pages
		);
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_conversions_list',
			'type'       => 'object',
			'properties' => array(
				'order_id'         => array(
					'description' => __( 'Order ID.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'order_number'     => array(
					'description' => __( 'Order Number.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'     => array(
					'description' => __( "Date the order was created, in the site's timezone.", 'automatewoo' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt' => array(
					'description' => __( 'Date the order was created, as GMT.', 'automatewoo' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id'      => array(
					'description' => __( 'Customer ID.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_sales'      => array(
					'description' => __( 'Order total.', 'automatewoo' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_formatted'  => array(
					'description' => __( 'Order total (formatted).', 'automatewoo' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'workflow_id'      => array(
					'description' => __( 'Workflow ID which triggered the conversion.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'conversion_id'    => array(
					'description' => __( 'Conversion log ID.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'extended_info'    => array(
					'customer'   => array(
						'type'        => 'object',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Order customer information.', 'automatewoo' ),
					),
					'workflow'   => array(
						'type'        => 'object',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Workflow information.', 'automatewoo' ),
					),
					'conversion' => array(
						'type'        => 'object',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Conversion information.', 'automatewoo' ),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['extended_info'] = array(
			'description'       => __( 'Add additional info about each conversion to the report.', 'automatewoo' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}

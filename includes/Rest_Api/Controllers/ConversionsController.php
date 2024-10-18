<?php

namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\Permissions;
use AutomateWoo\Rest_Api\Utilities\RestException;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Conversions Rest API controller.
 *
 * @since 5.7.0
 */
class ConversionsController extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'conversions';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<id>[\d]+)",
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'unmark_conversion' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => [
						'id' => [
							'type'        => 'integer',
							'description' => __( 'Conversion Order ID.', 'automatewoo' ),
							'context'     => [ 'view' ],
							'required'    => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/batch",
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'batch_unmark_conversions' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => [
						'ids' => [
							'type'        => 'array',
							'description' => __( 'List of Conversion Order IDs.', 'automatewoo' ),
							'minItems'    => 1,
							'required'    => true,
							'uniqueItems' => true,
							'items'       => [
								'type' => 'integer',
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Unmarks a conversion.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function unmark_conversion( $request ) {
		try {
			$this->remove_order_conversion( $request->get_param( 'id' ) );

			return rest_ensure_response(
				[
					'message' => __( 'Unmarked as conversion.', 'automatewoo' ),
				]
			);
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		} catch ( Exception $e ) {
			return $this->get_rest_error_from_exception( $e );
		}
	}

	/**
	 * Batch unmarks a list of conversions.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function batch_unmark_conversions( $request ) {
		try {
			foreach ( $request->get_param( 'ids' ) as $id ) {
				$this->remove_order_conversion( $id );
			}

			return rest_ensure_response(
				[
					'message' => __( 'Unmarked conversions.', 'automatewoo' ),
				]
			);
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		} catch ( Exception $e ) {
			return $this->get_rest_error_from_exception( $e );
		}
	}

	/**
	 * Remove conversion from an order.
	 *
	 * @param int $order_id
	 *
	 * @throws RestException When the order does not exist.
	 */
	protected function remove_order_conversion( int $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order === false ) {
			throw new RestException(
				'rest_invalid_order_id',
				sprintf(
					/* translators: Order ID. */
					esc_html__( 'Invalid order ID %d.', 'automatewoo' ),
					(int) $order_id
				),
				404
			);
		}

		$order->delete_meta_data( '_aw_conversion' );
		$order->delete_meta_data( '_aw_conversion_log' );
		$order->save();
	}
}

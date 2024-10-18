<?php

namespace AutomateWoo\Rest_Api\Utilities;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST API pagination utility class.
 *
 * Based off of
 *
 * @since 4.9.0
 */
class Pagination {

	/**
	 * The REST request object.
	 *
	 * @var WP_REST_Request
	 */
	protected $request;

	/**
	 * The REST response object.
	 *
	 * @var WP_REST_Request
	 */
	protected $response;

	/**
	 * The total items found.
	 *
	 * @var int
	 */
	protected $total_items;

	/**
	 * Pagination constructor.
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 * @param int              $total_items
	 */
	public function __construct( WP_REST_Request $request, WP_REST_Response $response, $total_items ) {
		$this->request     = $request;
		$this->response    = $response;
		$this->total_items = absint( $total_items );
	}

	/**
	 * Add pagination headers to a response object and then return it.
	 *
	 * @return WP_REST_Response
	 */
	public function add_headers() {
		$current_page   = absint( $this->request->get_param( 'page' ) );
		$items_per_page = absint( $this->request->get_param( 'per_page' ) );
		$total_pages    = ceil( $this->total_items / $items_per_page );
		$link_base      = $this->get_link_base();

		$this->response->header( 'X-WP-Total', $this->total_items );
		$this->response->header( 'X-WP-TotalPages', $total_pages );

		if ( $current_page > 1 ) {
			$previous_page = $current_page - 1;
			if ( $previous_page > $total_pages ) {
				$previous_page = $total_pages;
			}
			$this->add_page_link( 'prev', $previous_page, $link_base );
		}

		if ( $total_pages > $current_page ) {
			$this->add_page_link( 'next', ( $current_page + 1 ), $link_base );
		}

		return $this->response;
	}

	/**
	 * Get base for links from the request object.
	 *
	 * @return string
	 */
	protected function get_link_base() {
		// SEMGREP WARNING EXPLANATION
		// This is escaped later in the add_page_link function.
		return add_query_arg( $this->request->get_query_params(), rest_url( $this->request->get_route() ) );
	}

	/**
	 * Add a page link.
	 *
	 * @param string $name      Page link name. e.g. prev.
	 * @param int    $page      Page number.
	 * @param string $link_base Base URL.
	 */
	protected function add_page_link( $name, $page, $link_base ) {
		// SEMGREP WARNING EXPLANATION
		// This is escaped by esc_url_raw, but semgrep only takes into consideration esc_url.
		$this->response->link_header( $name, esc_url_raw( add_query_arg( 'page', $page, $link_base ) ) );
	}
}

<?php
/**
 * Class for parameter-based Email & SMS Tracking Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'    => '2018-07-19 00:00:00',
 *          'after'     => '2018-07-05 00:00:00',
 *          'page'      => 2,
 *          'workflows' => array(5, 120),
 *         );
 * $report = new \AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking\Query( $args );
 * $mydata = $report->get_data();
 */

namespace AutomateWoo\Admin\Analytics\Rest_API\Upstream;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Query as WooReportsQuery;
use WC_Data_Store;

/**
 * Workflow runs specific Woo Report Query.
 *
 * @see Automattic\WooCommerce\Admin\API\Reports\Query
 * @since 5.6.9
 */
class Generic_Query extends \WC_Object_Query {

	/**
	 * Store name
	 *
	 * @var string
	 */
	public $store_name;

	/**
	 * Create a new query.
	 *
	 * @param array  $args Criteria to query on in a format similar to WP_Query.
	 * @param string $store_name `WC_Data_Store`'s store name to eventually load and get data from.
	 * @extends WooReportsQuery::_construct
	 */
	public function __construct( $args, $store_name ) {
		$this->store_name = $store_name;

		parent::__construct( $args );
	}
	/**
	 * Valid fields for Products report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get emails tracking data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$data_store = WC_Data_Store::load( $this->store_name );
		return $data_store->get_data( $this->get_query_vars() );
	}
}

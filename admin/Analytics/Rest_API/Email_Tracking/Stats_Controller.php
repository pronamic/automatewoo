<?php
namespace AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Log_Stats_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;

/**
 * REST API Reports Email & SMS Tracking stats controller class.
 *
 * @extends Log_Stats_Controller
 */
class Stats_Controller extends Log_Stats_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/email-tracking/stats';

	/**
	 * Forwards a Email & SMS Tracking Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-email-tracking-stats' );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array(
			'sent'          => array(
				'description' => __( 'Number of sent messages.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'opens'         => array(
				'description' => __( 'Number of opened messages.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'unique-clicks' => array(
				'description' => __( 'Number of messages clicked at least ones.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'clicks'        => array(
				'description' => __( 'Number of total clicks.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
	/**
	 * Get the Email & SMS Tracking Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_email_tracking_stats';

		return $this->add_additional_fields_schema( $schema );
	}
}

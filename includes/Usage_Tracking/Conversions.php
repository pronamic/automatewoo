<?php

namespace AutomateWoo\Usage_Tracking;

use AutomateWoo\Log;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track Conversions.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
class Conversions implements Event_Tracker_Interface {

	use Event_Helper;

	/**
	 * Initialize the tracking class with various hooks.
	 */
	public function init() {
		add_action( 'automatewoo/conversion/recorded', [ $this, 'track_conversion' ], 10, 2 );
	}

	/**
	 * Track a conversion event.
	 *
	 * @param WC_Order $order The order object.
	 * @param Log      $log   The log that triggered the conversion.
	 */
	public function track_conversion( $order, $log ) {
		$workflow = $log->get_workflow();
		$this->record_event(
			'conversion_recorded',
			[
				'order_currency'    => $order->get_currency(),
				'order_total'       => $order->get_total(),
				'workflow_run_date' => $log->get_date()->to_mysql_string(),
				'workflow_trigger'  => $workflow->get_trigger_name(),
				'workflow_title'    => $workflow->get_title(),
			]
		);
	}
}

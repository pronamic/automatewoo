<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Time_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the Site timezone offset changed in the site.
 * This check is needed to re-schedule the Custom Day Time based hooks using the new timezone.
 *
 * @since 6.0.0
 */
class CheckGmtOffsetChange extends AbstractRecurringOneTimeActionSchedulerJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'check_for_gmt_offset_change';
	}


	/**
	 * Check if the new site timezone offset matches with the existing one. If it doesn't match, it updates `automatewoo_gmt_offset`
	 * with the new site timezone offset and triggers `automatewoo/gmt_offset_changed` action.
	 *
	 * @param array $item Not being used for this action.
	 */
	protected function process_item( array $item ) {
		$new_offset      = Time_Helper::get_timezone_offset();
		$existing_offset = get_option( 'automatewoo_gmt_offset' );

		if ( $existing_offset === false ) {
			update_option( 'automatewoo_gmt_offset', $new_offset, false );
			return;
		}

		if ( (float) $existing_offset !== (float) $new_offset ) {
			do_action( 'automatewoo/gmt_offset_changed', $new_offset, $existing_offset );
			update_option( 'automatewoo_gmt_offset', $new_offset, false );
		}
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for this action
	 */
	public function get_interval() {
		return JobService::FIVE_MINUTE_INTERVAL;
	}
}

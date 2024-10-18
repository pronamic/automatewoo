<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Carts;
use AutomateWoo\Options;

defined( 'ABSPATH' ) || exit;

/**
 * One time Recurring Job for cleaning the inactive stored carts.
 * They are cleaned based on the Setting Clear Inactive Carts After
 *
 * @since 6.0.0
 */
class CleanInactiveCarts extends AbstractRecurringOneTimeActionSchedulerJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'clean_inactive_carts';
	}


	/**
	 * Call clean_stored_carts() method for removing inactive stored carts.
	 *
	 * @param array $item Not being used for this action.
	 */
	protected function process_item( array $item ) {
		Carts::clean_stored_carts();
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for this action
	 */
	public function get_interval() {
		return JobService::TWO_DAY_INTERVAL;
	}

	/**
	 * If cart tracking is not enabled then disable the job to prevent
	 * recurring actions from being scheduled.
	 *
	 * @since 6.0.28
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return Options::abandoned_cart_enabled();
	}
}

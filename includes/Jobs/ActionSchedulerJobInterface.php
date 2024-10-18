<?php

namespace AutomateWoo\Jobs;

/**
 * Interface ActionSchedulerJobInterface.
 *
 * @since 5.2.0
 */
interface ActionSchedulerJobInterface extends JobInterface {

	/**
	 * Get the hook name for the "process item" action.
	 *
	 * This method is required by the job monitor.
	 *
	 * @return string
	 */
	public function get_process_item_hook();
}

<?php

namespace AutomateWoo\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface StartOnHookInterface.
 *
 * Jobs that implement this interface will start on a specific action hook.
 *
 * @since 5.1.0
 */
interface StartOnHookInterface extends JobInterface {

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return string
	 */
	public function get_start_hook();
}

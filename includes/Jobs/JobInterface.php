<?php

namespace AutomateWoo\Jobs;

/**
 * Interface JobInterface.
 *
 * @since   5.0.0
 * @package AutomateWoo\Jobs
 */
interface JobInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Init the job.
	 */
	public function init();
}

<?php

namespace AutomateWoo\Jobs;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Interface BatchedActionSchedulerJobInterface.
 *
 * @since 5.2.0
 */
interface BatchedActionSchedulerJobInterface extends ActionSchedulerJobInterface {

	/**
	 * Start the job.
	 *
	 * @param array $args Optionally set args to be available during this instance of the job.
	 */
	public function start( array $args = [] );

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked automatewoo/jobs/{$job_name}/create_batch
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_create_batch_action( int $batch_number, array $args );

	/**
	 * Handles processing single item action hook.
	 *
	 * @hooked automatewoo/jobs/{$job_name}/process_item
	 *
	 * @param mixed $item A single job item from the current batch.
	 * @param array $args The args for this instance of the job.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_process_item_action( $item, array $args );
}

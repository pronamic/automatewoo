<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Exceptions\InvalidArgument;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Interface OneTimeActionSchedulerJobInterface.
 *
 * A "one time job" is a job that receives all the items it needs to process immediately instead of in batches.
 *
 * @since 5.2.0
 */
interface OneTimeActionSchedulerJobInterface extends ActionSchedulerJobInterface {

	/**
	 * Starts the job.
	 *
	 * @param array[] $items The items to process in the job.
	 *                       Items are stored in the database so don't include full objects.
	 *
	 * @throws InvalidArgument If an item is not valid.
	 */
	public function start( array $items = [] );

	/**
	 * Handles the process item job action.
	 *
	 * @param array $item
	 *
	 * @throws Exception If an error occurs.
	 * @throws InvalidArgument If args or an item is invalid.
	 */
	public function handle_process_item_action( array $item );
}

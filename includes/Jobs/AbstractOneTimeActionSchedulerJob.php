<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\ArrayValidator;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractOneTimeActionSchedulerJob class.
 *
 * A "one time job" is a job that receives all the items it needs to process immediately instead of in batches.
 *
 * @since 5.2.0
 */
abstract class AbstractOneTimeActionSchedulerJob extends AbstractActionSchedulerJob implements OneTimeActionSchedulerJobInterface {

	use ArrayValidator;

	/**
	 * Process a single item.
	 *
	 * @param array $item A single item to process. Expects a validated item.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function process_item( array $item );

	/**
	 * Init the batch schedule for the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function init() {
		add_action( $this->get_process_item_hook(), [ $this, 'handle_process_item_action' ] );
	}

	/**
	 * Starts the job.
	 *
	 * @param array[] $items The items to process in the job.
	 *                       Items are stored in the database so don't include full objects.
	 *
	 * @throws InvalidArgument If an item is not valid.
	 */
	public function start( array $items = [] ) {
		// Validate all items before adding scheduling any.
		foreach ( $items as $item ) {
			$this->validate_item( $item );
		}

		foreach ( $items as $item ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $item ] );
		}
	}

	/**
	 * Handles the process item job action.
	 *
	 * @param array $item
	 *
	 * @throws Exception If an error occurs.
	 * @throws InvalidArgument If args or an item is invalid.
	 */
	public function handle_process_item_action( array $item = [] ) {
		$this->validate_item( $item );
		$this->process_item( $item );
	}

	/**
	 * Validate an item to be processed by the job.
	 *
	 * @param array $item
	 *
	 * @throws InvalidArgument If the item is not valid.
	 */
	protected function validate_item( array $item ) {
		$this->validate_is_non_empty_array( $item );
	}
}

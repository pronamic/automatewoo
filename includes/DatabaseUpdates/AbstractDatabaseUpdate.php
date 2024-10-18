<?php

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Logger;

/**
 * Class AbstractDatabaseUpdate
 *
 * @since   2.9.7
 * @package AutomateWoo\DatabaseUpdates
 */
abstract class AbstractDatabaseUpdate {

	/**
	 * Whether the update is completed after the process has been dispatched.
	 *
	 * @var bool
	 */
	protected $is_complete = false;

	/**
	 * A count of items processed in the current dispatch process.
	 *
	 * @var int
	 */
	protected $items_processed = 0;

	/**
	 * The version number for the update.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Process a database update batch.
	 *
	 * This method will be continue being called in separate wp-ajax requests until it returns true.
	 *
	 * @return bool Return true if update is complete, false if not yet complete.
	 */
	abstract protected function process();

	/**
	 * Runs immediately before a database update begins.
	 */
	protected function start() {
		Logger::info( 'updates', "AutomateWoo - Started {$this->version} database update" );
	}

	/**
	 * Called immediately after database update is completed.
	 */
	protected function finish() {
		Logger::info( 'updates', "AutomateWoo - Finished {$this->version} database update" );
	}

	/**
	 * Get the option name that stores if the update has started.
	 *
	 * @return string
	 */
	protected function get_started_option_name() {
		return 'automatewoo_db_update_started_' . str_replace( '.', '_', $this->version );
	}

	/**
	 * Dispatches a call to start or continue processing a database update.
	 */
	public function dispatch_process() {
		if ( ! get_option( $this->get_started_option_name() ) ) {
			// Set an option to track whether the update has started
			update_option( $this->get_started_option_name(), 1, true );
			$this->start();
		}

		$was_completed = $this->process();

		if ( $was_completed ) {
			delete_option( $this->get_started_option_name() );
			$this->is_complete = true;
			$this->finish();
		}
	}

	/**
	 * Returns true if the update is completed with caveats.
	 * - It only knows if the process was completed after calling `dispatch_process()`.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->is_complete;
	}

	/**
	 * Returns the count of items run in a single request, not a running total.
	 *
	 * @return int
	 */
	public function get_items_processed_count() {
		return $this->items_processed;
	}

	/**
	 * Return 0 if the total is unknown.
	 *
	 * @return int
	 * @since 4.3.0
	 */
	public function get_items_to_process_count() {
		return 0;
	}

	/**
	 * Log an error while processing the update.
	 *
	 * @param string $message
	 */
	protected function log_processing_error( string $message ) {
		Logger::error(
			'updates',
			sprintf( 'AutomateWoo - Update: %s, Message: %s', $this->version, $message )
		);
	}
}

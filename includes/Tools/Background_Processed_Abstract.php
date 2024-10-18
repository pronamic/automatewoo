<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Jobs\ToolTaskRunner;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Abstract class for tools that are processed in the background.
 *
 * @since 3.8
 */
abstract class Tool_Background_Processed_Abstract extends Tool_Abstract {

	/** @var bool */
	public $is_background_processed = true;


	function __construct() {
		$this->additional_description = __( 'If you are processing a large number of items they will be processed in the background.', 'automatewoo' );
	}


	/**
	 * Method to handle individual background tasks.
	 * $task array will not be sanitized.
	 *
	 * @param array $task
	 * @return void
	 */
	abstract public function handle_background_task( $task );

	/**
	 * Start the ToolRunner background job.
	 *
	 * @since 5.2.0
	 *
	 * @param array $tasks
	 * @return bool|\WP_Error
	 */
	protected function start_background_job( array $tasks ) {
		try {
			/** @var ToolTaskRunner $job */
			$job = AW()->job_service()->get_job( 'tools' );
			$job->start( $tasks );
		} catch ( \Exception $e ) {
			Logger::error( 'tools', "Error encountered when starting the ToolRunner job: {$e->getMessage()}" );
			return new WP_Error( 'tool', __( "An error occurred. Please check the 'automatewoo-tools' logs for more info.", 'automatewoo' ) );
		}

		return true;
	}

}


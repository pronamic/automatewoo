<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Tool_Background_Processed_Abstract;
use AutomateWoo\Tools\ToolsService;
use Exception;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ToolTaskRunner
 *
 * Runs background tasks as required by AutomateWoo\Tools.
 *
 * @since 5.2.0.
 */
class ToolTaskRunner extends AbstractOneTimeActionSchedulerJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'tools';
	}

	/**
	 * @var ToolsService
	 */
	protected $tools_service;

	/**
	 * ToolTaskRunner constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ToolsService              $tools
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ToolsService $tools ) {
		$this->tools_service = $tools;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Process a single item.
	 *
	 * @param array $item A single item to process. Expects a validated item.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 * @throws RuntimeException If tool is not found.
	 */
	protected function process_item( array $item ) {
		$tool = $this->tools_service->get_tool( $item['tool_id'] );
		if ( ! $tool || ! $tool instanceof Tool_Background_Processed_Abstract ) {
			throw new RuntimeException( 'Valid tool not found.' );
		}

		$tool->handle_background_task( $item );
	}

	/**
	 * Validate an item to be processed by the job.
	 *
	 * @param array $item
	 *
	 * @throws InvalidArgument If the item is not valid.
	 */
	protected function validate_item( array $item ) {
		parent::validate_item( $item );

		if ( ! isset( $item['tool_id'] ) ) {
			throw InvalidArgument::missing_required( 'tool_id' );
		}
	}
}

<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Exceptions\InvalidClass;
use AutomateWoo\OptionsStore;
use AutomateWoo\Tools\ToolsService;
use AutomateWoo\Traits\ArrayValidator;
use AutomateWoo\Workflows\Factory as WorkflowsFactory;

defined( 'ABSPATH' ) || exit;

/**
 * JobRegistry class.
 *
 * @since 5.1.0
 */
class JobRegistry implements JobRegistryInterface {

	use ArrayValidator;

	/**
	 * Array of job objects with their names as keys.
	 *
	 * @var JobInterface[]
	 */
	protected $jobs;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * @var OptionsStore
	 */
	protected $options_store;

	/**
	 * @var ToolsService
	 */
	protected $tools_service;

	/**
	 * BatchedJobInitializer constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 * @param OptionsStore             $options_store
	 * @param ToolsService             $tools_service
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, OptionsStore $options_store, ToolsService $tools_service ) {
		$this->action_scheduler = $action_scheduler;
		$this->options_store    = $options_store;
		$this->tools_service    = $tools_service;
	}

	/**
	 * Get a single registered job.
	 *
	 * @param string $name
	 *
	 * @return JobInterface
	 *
	 * @throws InvalidClass|InvalidArgument When there is an error loading jobs.
	 * @throws JobException If the job is not found.
	 */
	public function get( string $name ): JobInterface {
		$this->load_jobs();

		if ( ! isset( $this->jobs[ $name ] ) ) {
			throw JobException::job_does_not_exist( esc_html( $name ) );
		}

		return $this->jobs[ $name ];
	}

	/**
	 * Get an array of all registered jobs.
	 *
	 * @return JobInterface[]
	 *
	 * @throws InvalidClass|InvalidArgument When there is an error loading jobs.
	 */
	public function list(): array {
		$this->load_jobs();

		return $this->jobs;
	}

	/**
	 * Load jobs.
	 *
	 * Only loads jobs the first time it's called.
	 *
	 * @throws InvalidArgument|InvalidClass When there is an error loading jobs.
	 */
	protected function load_jobs() {
		if ( isset( $this->jobs ) ) {
			return;
		}
		$this->jobs          = [];
		$batched_job_monitor = new ActionSchedulerJobMonitor( $this->action_scheduler );

		$midnight_job = new Midnight( $this->action_scheduler, $batched_job_monitor );

		$jobs = [
			new DeleteFailedQueuedWorkflows( $this->action_scheduler, $batched_job_monitor ),
			new RunQueuedWorkflows( $this->action_scheduler, $batched_job_monitor ),
			new SetupRegisteredCustomers( $this->action_scheduler, $batched_job_monitor ),
			new SetupGuestCustomers( $this->action_scheduler, $batched_job_monitor ),
			new BatchedWorkflows(
				$this->action_scheduler,
				$batched_job_monitor,
				function ( $id ) {
					return WorkflowsFactory::get( $id );
				}
			),
			new DeleteExpiredCoupons( $this->action_scheduler, $batched_job_monitor, $this->options_store ),
			new AbandonedCarts( $this->action_scheduler, $batched_job_monitor, $this->options_store ),
			new WishlistItemOnSale( $this->action_scheduler, $batched_job_monitor ),
			new ToolTaskRunner( $this->action_scheduler, $batched_job_monitor, $this->tools_service ),
			$midnight_job,
			new CheckMidnightJob( $this->action_scheduler, $batched_job_monitor, $midnight_job ),
			new CheckGmtOffsetChange( $this->action_scheduler, $batched_job_monitor ),
			new ProductGoesOnSale( $this->action_scheduler, $batched_job_monitor ),
			new CleanInactiveCarts( $this->action_scheduler, $batched_job_monitor ),
		];

		/**
		 * Apply filter to registered job objects.
		 *
		 * @param JobInterface[] $jobs
		 *
		 * @since 5.1.0
		 */
		$jobs = apply_filters( 'automatewoo/jobs', $jobs );

		// Ensure $jobs is still an array
		$this->validate_is_array( $jobs );

		foreach ( $jobs as $job ) {
			if ( ! $job instanceof JobInterface ) {
				throw InvalidClass::does_not_implement_interface( esc_html( get_class( $job ) ), JobInterface::class );
			}

			$this->jobs[ $job->get_name() ] = $job;
		}
	}
}

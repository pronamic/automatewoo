<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Exceptions\InvalidClass;
use AutomateWoo\Traits\ArrayValidator;

defined( 'ABSPATH' ) || exit;

/**
 * JobService class.
 *
 * @version 5.1.0
 */
class JobService {

	use ArrayValidator;

	// Job intervals for using in the recurrent Jobs
	const ONE_MINUTE_INTERVAL     = 60;
	const TWO_MINUTE_INTERVAL     = self::ONE_MINUTE_INTERVAL * 2;
	const FIVE_MINUTE_INTERVAL    = self::ONE_MINUTE_INTERVAL * 5;
	const FIFTEEN_MINUTE_INTERVAL = self::ONE_MINUTE_INTERVAL * 15;
	const THIRTY_MINUTE_INTERVAL  = self::ONE_MINUTE_INTERVAL * 30;
	const ONE_HOUR_INTERVAL       = self::ONE_MINUTE_INTERVAL * 60;
	const FOUR_HOURS_INTERVAL     = self::ONE_HOUR_INTERVAL * 4;
	const ONE_DAY_INTERVAL        = self::ONE_HOUR_INTERVAL * 24;
	const TWO_DAY_INTERVAL        = self::ONE_DAY_INTERVAL * 2;
	const WEEKLY_INTERVAL         = self::ONE_DAY_INTERVAL * 7;

	/**
	 * @var JobRegistryInterface
	 */
	protected $registry;

	/**
	 * JobService constructor.
	 *
	 * @param JobRegistryInterface $registry
	 */
	public function __construct( JobRegistryInterface $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Initialize all jobs.
	 *
	 * @throws InvalidClass|InvalidArgument When there is an error loading jobs.
	 */
	public function init_jobs() {
		foreach ( $this->registry->list() as $job ) {
			$job->init();

			if ( $job instanceof StartOnHookInterface ) {
				$this->check_is_using_deprecated_cron_workers( $job );
				add_action( $job->get_start_hook(), [ $job, 'start' ], 10, 0 );
			}

			if ( $job instanceof RecurringJobInterface ) {
				// ActionScheduler loads its tables on "init" action
				add_action( 'admin_init', [ $job, 'schedule_recurring' ], 10, 0 );

				// Cancel the recurring action on deactivation
				register_deactivation_hook( AUTOMATEWOO_FILE, [ $job, 'cancel_recurring' ] );
			}
		}
	}

	/**
	 * Get all the jobs
	 *
	 * @since 6.0.0
	 * @return JobInterface[] All the Jobs
	 *
	 * @throws InvalidClass|InvalidArgument When there is an error loading jobs.
	 */
	public function get_jobs(): array {
		return $this->registry->list();
	}

	/**
	 * Get a job by name.
	 *
	 * @param string $name The job name.
	 *
	 * @return JobInterface
	 *
	 * @throws JobException If the job is not found.
	 * @throws InvalidClass|InvalidArgument When there is an invalid job class.
	 */
	public function get_job( string $name ): JobInterface {
		return $this->registry->get( $name );
	}

	/**
	 * Check if the start hook is relying on legacy Cron workers. If so, it shows a deprecation notice.
	 *
	 * @since 6.0.0
	 * @param StartOnHookInterface $job The job to check.
	 */
	public function check_is_using_deprecated_cron_workers( $job ) {

		$legacy_cron_workers = array_map(
			function ( $worker ) {
				return 'automatewoo_' . $worker . '_worker';
			},
			[
				'events',
				'one_minute',
				'two_minute',
				'five_minute',
				'fifteen_minute',
				'thirty_minute',
				'hourly',
				'four_hourly',
				'daily',
				'two_days',
				'weekly',
			]
		);

		if ( in_array( $job->get_start_hook(), $legacy_cron_workers, true ) ) {
			wc_deprecated_hook( $job->get_start_hook(), '6.0.0', 'Action Scheduler Recurrent Jobs', 'See \AutomateWoo\Jobs\RecurringJobInterface' );
		}
	}
}

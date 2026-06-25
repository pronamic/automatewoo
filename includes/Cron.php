<?php

namespace AutomateWoo;

/**
 * Cron manager
 *
 * @class Cron
 */
class Cron {

	const TWO_MINUTE_WORKER     = 'automatewoo_two_minute_worker';
	const FIVE_MINUTE_WORKER    = 'automatewoo_five_minute_worker';
	const FIFTEEN_MINUTE_WORKER = 'automatewoo_fifteen_minute_worker';
	const THIRTY_MINUTE_WORKER  = 'automatewoo_thirty_minute_worker';
	const HOURLY_WORKER         = 'automatewoo_hourly_worker';
	const FOUR_HOUR_WORKER      = 'automatewoo_four_hourly_worker';
	const DAILY_WORKER          = 'automatewoo_daily_worker';
	const TWO_DAY_WORKER        = 'automatewoo_two_days_worker';
	const WEEKLY_WORKER         = 'automatewoo_weekly_worker';

	/**
	 * Map of worker name to schedule slug.
	 *
	 * @var array
	 */
	public static $workers = [
		'events'         => 'automatewoo_one_minute',
		'two_minute'     => 'automatewoo_two_minutes',
		'five_minute'    => 'automatewoo_five_minutes',
		'fifteen_minute' => 'automatewoo_fifteen_minutes',
		'thirty_minute'  => 'automatewoo_thirty_minutes',
		'hourly'         => 'hourly',
		'four_hourly'    => 'automatewoo_four_hours',
		'daily'          => 'daily',
		'two_days'       => 'automatewoo_two_days',
		'weekly'         => 'automatewoo_weekly',
	];


	/**
	 * Init cron
	 */
	public static function init() {

		// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- Intentional custom interval for queue processing.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_schedules' ], 100 );

		foreach ( self::$workers as $worker => $schedule ) {
			add_action( 'automatewoo_' . $worker . '_worker', [ __CLASS__, 'before_worker' ], 1 );
		}

		add_action( 'admin_init', [ __CLASS__, 'add_events' ] );

		// Un-schedule legacy  WP Cron based 'automatewoo_midnight'
		// Now this job runs via ActionScheduler
		wp_unschedule_hook( 'automatewoo_midnight' );

		register_deactivation_hook( AUTOMATEWOO_FILE, [ __CLASS__, 'remove_events' ] );
	}


	/**
	 * Remove all cron events on plugin deactivation.
	 *
	 * @since 6.5.0
	 */
	public static function remove_events() {
		foreach ( self::$workers as $worker => $schedule ) {
			wp_unschedule_hook( 'automatewoo_' . $worker . '_worker' );
		}
		wp_unschedule_hook( 'automatewoo_midnight' );
	}


	/**
	 * Prevents workers from working if they have done so in the past 30 seconds
	 */
	public static function before_worker() {

		$action = current_action();

		if ( self::is_worker_locked( $action ) ) {
			remove_all_actions( $action ); // prevent actions from running
			return;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Defensive: set_time_limit may be disabled by the host.
		@set_time_limit( 300 );

		self::update_last_run( $action );
	}


	/**
	 * Get the last run time for a worker action.
	 *
	 * @param string $action Worker action hook name.
	 * @return \DateTime|bool
	 */
	public static function get_last_run( $action ) {
		$last_runs = get_option( 'aw_workers_last_run' );
		if ( is_array( $last_runs ) && isset( $last_runs[ $action ] ) ) {
			$date = new DateTime();
			$date->setTimestamp( $last_runs[ $action ] );
			return $date;
		} else {
			return false;
		}
	}


	/**
	 * Record the current time as the last run for a worker action.
	 *
	 * @param string $action Worker action hook name.
	 */
	public static function update_last_run( $action ) {
		$last_runs = get_option( 'aw_workers_last_run' );

		if ( ! $last_runs ) {
			$last_runs = [];
		}

		$last_runs[ $action ] = time();

		update_option( 'aw_workers_last_run', $last_runs, false );
	}



	/**
	 * Checks if worker started running less than 30 seconds
	 *
	 * @param string $action Worker action hook name.
	 * @return bool
	 */
	public static function is_worker_locked( $action ) {
		$time_last_run = self::get_last_run( $action );
		if ( ! $time_last_run ) {
			return false;
		}

		$time_unlocked = clone $time_last_run;
		$time_unlocked->modify( '+30 seconds' );

		if ( $time_unlocked->getTimestamp() > time() ) {
			return true;
		}

		return false;
	}


	/**
	 * Add cron workers
	 */
	public static function add_events() {
		foreach ( self::$workers as $worker => $schedule ) {
			$hook = 'automatewoo_' . $worker . '_worker';

			if ( 'events' === $worker && ! self::has_worker_callbacks( $hook ) ) {
				wp_clear_scheduled_hook( $hook );
				continue;
			}

			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time(), $schedule, $hook );
			}
		}
	}


	/**
	 * Checks if a worker hook has callbacks other than the generic lock guard.
	 *
	 * @param string $hook Worker hook name.
	 * @return bool
	 */
	private static function has_worker_callbacks( $hook ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
			return false;
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( ! self::is_before_worker_callback( $callback['function'] ) ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Checks if a callback is the generic worker lock guard.
	 *
	 * @param callable $callback Callback to check.
	 * @return bool
	 */
	private static function is_before_worker_callback( $callback ) {
		return is_array( $callback )
			&& isset( $callback[0], $callback[1] )
			&& __CLASS__ === $callback[0]
			&& 'before_worker' === $callback[1];
	}


	/**
	 * Register AutomateWoo custom cron schedules.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public static function add_schedules( $schedules ) {

		$schedules['automatewoo_one_minute'] = [
			'interval' => 60,
			'display'  => __( 'One minute', 'automatewoo' ),
		];

		$schedules['automatewoo_two_minutes'] = [
			'interval' => 120,
			'display'  => __( 'Two minutes', 'automatewoo' ),
		];

		$schedules['automatewoo_five_minutes'] = [
			'interval' => 300,
			'display'  => __( 'Five minutes', 'automatewoo' ),
		];

		$schedules['automatewoo_fifteen_minutes'] = [
			'interval' => 900,
			'display'  => __( 'Fifteen minutes', 'automatewoo' ),
		];

		$schedules['automatewoo_thirty_minutes'] = [
			'interval' => 1800,
			'display'  => __( 'Thirty minutes', 'automatewoo' ),
		];

		$schedules['automatewoo_two_days'] = [
			'interval' => 172800,
			'display'  => __( 'Two days', 'automatewoo' ),
		];

		$schedules['automatewoo_four_hours'] = [
			'interval' => 14400,
			'display'  => __( 'Four hours', 'automatewoo' ),
		];

		$schedules['automatewoo_weekly'] = [
			'interval' => 604800,
			'display'  => __( 'Once weekly', 'automatewoo' ),
		];

		return $schedules;
	}
}

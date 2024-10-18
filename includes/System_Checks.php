<?php

namespace AutomateWoo;

use AutomateWoo\SystemChecks\AbstractSystemCheck;
use AutomateWoo\SystemChecks\ActionSchedulerJobsRunning;
use AutomateWoo\SystemChecks\DatabaseTablesExist;

/**
 * System check management class
 *
 * @class System_Checks
 */
class System_Checks {

	/** @var array */
	public static $system_checks;

	/**
	 * @return AbstractSystemCheck[]
	 */
	public static function get_all() {
		if ( ! isset( self::$system_checks ) ) {
			$class_names = apply_filters(
				'automatewoo/system_checks',
				[
					'action_scheduler_jobs_running' => ActionSchedulerJobsRunning::class,
					'database_tables_exist'         => DatabaseTablesExist::class,
				]
			);

			foreach ( $class_names as $system_check_id => $class_name ) {
				self::$system_checks[ $system_check_id ] = new $class_name();
			}
		}

		return self::$system_checks;
	}


	/**
	 * Maybe background check for high priority issues
	 */
	public static function maybe_schedule_check() {

		if ( did_action( 'automatewoo_installed' ) ) {
			return; // bail if just installed
		}

		if ( ! AW()->options()->enable_background_system_check || get_transient( 'automatewoo_background_system_check' ) ) {
			return;
		}

		AW()->action_scheduler()->schedule_single( gmdate( 'U' ) + 120, 'automatewoo/system_check' );

		set_transient( 'automatewoo_background_system_check', true, DAY_IN_SECONDS * 4 );
	}


	/**
	 * Runs all the system checks
	 */
	public static function run_system_check() {

		foreach ( self::get_all() as $check ) {

			if ( ! $check->high_priority ) {
				continue;
			}

			$response = $check->run();

			if ( $response['success'] === false ) {
				set_transient( 'automatewoo_background_system_check_errors', true, DAY_IN_SECONDS );
			}
		}
	}


	/**
	 * Display error notices (if any) in Status Page when there is manager capabilities.
	 */
	public static function maybe_display_notices() {
		if ( Admin::is_page( 'status' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) || ! get_transient( 'automatewoo_background_system_check_errors' ) ) {
			return;
		}

		$strong = __( 'AutomateWoo status check has found issues.', 'automatewoo' );
		/* translators: Status page URL. */
		$more = sprintf( __( '<a href="%s">View details</a>', 'automatewoo' ), Admin::page_url( 'status' ) );

		Admin::notice( 'error is-dismissible', $strong, $more, 'aw-notice-system-error' );
	}
}

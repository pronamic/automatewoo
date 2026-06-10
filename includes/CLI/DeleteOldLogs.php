<?php

namespace AutomateWoo\CLI;

use AutomateWoo\DateTime;
use AutomateWoo\Log_Query;
use AutomateWoo\LogCleanupService;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command to delete old AutomateWoo workflow logs.
 *
 * Uses the shared `LogCleanupService` so behaviour matches the
 * scheduled `DeleteOldLogs` Action Scheduler job.
 *
 * ## EXAMPLES
 *
 *     # Delete logs older than the configured retention period
 *     wp automatewoo delete-old-logs
 *
 *     # Delete logs older than a specific date
 *     wp automatewoo delete-old-logs --before-date=2024-01-01
 *
 *     # Dry run to see how many logs would be deleted
 *     wp automatewoo delete-old-logs --dry-run
 *
 *     # Delete with a custom batch size
 *     wp automatewoo delete-old-logs --batch-size=1000
 *
 * @since 6.3.2
 */
class DeleteOldLogs {

	/**
	 * Delete old workflow logs.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show how many logs would be deleted without making changes.
	 *
	 * [--before-date=<date>]
	 * : Delete logs before this date (Y-m-d format). Overrides the retention setting.
	 *
	 * [--batch-size=<number>]
	 * : Number of logs to delete per batch. Default: 500.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$dry_run    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$batch_size = absint( \WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', LogCleanupService::DEFAULT_BATCH_SIZE ) );

		if ( $batch_size < 1 ) {
			$batch_size = LogCleanupService::DEFAULT_BATCH_SIZE;
		}

		$service     = new LogCleanupService();
		$before_date = $this->resolve_before_date( $assoc_args, $service );

		if ( ! $before_date ) {
			WP_CLI::error( 'No retention period configured and no --before-date specified. Set a log retention period in AutomateWoo settings or pass --before-date.' );
			return;
		}

		WP_CLI::log( sprintf( 'Deleting logs older than %s ...', $before_date->format( 'Y-m-d H:i:s' ) ) );

		if ( $dry_run ) {
			$count = $this->count_logs_before_date( $before_date );
			WP_CLI::success( sprintf( '[DRY RUN] %d log(s) would be deleted.', $count ) );
			return;
		}

		$total_deleted = 0;
		do {
			$deleted        = $service->delete_logs_before_date( $before_date, $batch_size );
			$total_deleted += $deleted;

			if ( $deleted > 0 ) {
				WP_CLI::log( sprintf( 'Deleted %d logs (total so far: %d)', $deleted, $total_deleted ) );
			}
		} while ( $deleted >= $batch_size );

		if ( $total_deleted > 0 ) {
			$service->invalidate_log_caches();
		}

		WP_CLI::success( sprintf( 'Done. %d log(s) deleted.', $total_deleted ) );
	}

	/**
	 * Resolve the before-date from CLI args or the configured retention setting.
	 *
	 * @param array             $assoc_args CLI associative arguments.
	 * @param LogCleanupService $service    The log cleanup service.
	 *
	 * @return DateTime|false
	 */
	private function resolve_before_date( array $assoc_args, LogCleanupService $service ) {
		$date_arg = \WP_CLI\Utils\get_flag_value( $assoc_args, 'before-date', '' );

		if ( $date_arg ) {
			$parsed = \DateTime::createFromFormat( 'Y-m-d', $date_arg );

			if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date_arg ) {
				WP_CLI::error( sprintf( 'Invalid --before-date value "%s". Use Y-m-d format (e.g. 2024-01-01).', $date_arg ) );
			}

			try {
				$date = new DateTime( $date_arg );
				$date->setTime( 0, 0, 0 );
				return $date;
			} catch ( \Exception $e ) {
				WP_CLI::error( sprintf( 'Invalid --before-date value "%s". Use Y-m-d format (e.g. 2024-01-01).', $date_arg ) );
			}
		}

		$retention_months = AW()->options_store()->get_log_retention_months();

		return $service->get_retention_cutoff_date( $retention_months );
	}

	/**
	 * Count how many logs exist before a given date.
	 *
	 * @param DateTime $before_date The cutoff date.
	 *
	 * @return int
	 */
	private function count_logs_before_date( DateTime $before_date ): int {
		$query = new Log_Query();
		$query->where_date( $before_date, '<' );
		return $query->get_count();
	}
}

<?php

namespace AutomateWoo\CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Registers AutomateWoo WP-CLI commands.
 *
 * @since 6.3.2
 */
class CLIService {

	/**
	 * Register all CLI commands.
	 *
	 * Should only be called when `WP_CLI` is available.
	 *
	 * @since 6.3.2
	 */
	public static function register(): void {
		\WP_CLI::add_command( 'automatewoo add-log-indexes', AddLogIndexes::class );
		\WP_CLI::add_command( 'automatewoo delete-old-logs', DeleteOldLogs::class );
	}
}

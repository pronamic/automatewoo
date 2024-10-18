<?php

namespace AutomateWoo;

use WC_Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * @since 4.3.0
 */
class Logger {

	/** @var WC_Logger */
	protected static $wc_logger;

	/** @var string */
	protected static $handle_prefix = 'automatewoo-';


	/**
	 * @return WC_Logger
	 */
	protected static function get_wc_logger() {
		if ( empty( self::$wc_logger ) ) {
			self::$wc_logger = wc_get_logger();
		}
		return self::$wc_logger;
	}


	/**
	 * Add a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $handle
	 * @param string $message
	 */
	protected static function log( $level, $handle, $message ) {
		$handle = self::$handle_prefix . $handle;

		self::get_wc_logger()->log(
			$level,
			$message,
			[ 'source' => $handle ]
		);
	}


	/**
	 * Adds an emergency level message.
	 *
	 * System is unusable.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function emergency( $handle, $message ) {
		self::log( 'emergency', $handle, $message );
	}


	/**
	 * Adds an alert level message.
	 *
	 * Action must be taken immediately.
	 * Example: Entire website down, database unavailable, etc.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function alert( $handle, $message ) {
		self::log( 'alert', $handle, $message );
	}


	/**
	 * Adds a critical level message.
	 *
	 * Critical conditions.
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function critical( $handle, $message ) {
		self::log( 'critical', $handle, $message );
	}


	/**
	 * Adds an error level message.
	 *
	 * Runtime errors that do not require immediate action but should typically be logged
	 * and monitored.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function error( $handle, $message ) {
		self::log( 'error', $handle, $message );
	}


	/**
	 * Adds a warning level message.
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
	 * necessarily wrong.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function warning( $handle, $message ) {
		self::log( 'warning', $handle, $message );
	}


	/**
	 * Adds a notice level message.
	 *
	 * Normal but significant events.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function notice( $handle, $message ) {
		self::log( 'notice', $handle, $message );
	}


	/**
	 * Adds a info level message.
	 *
	 * Interesting events.
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function info( $handle, $message ) {
		self::log( 'info', $handle, $message );
	}


	/**
	 * Adds a debug level message.
	 *
	 * Detailed debug information.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function debug( $handle, $message ) {
		self::log( 'debug', $handle, $message );
	}
}

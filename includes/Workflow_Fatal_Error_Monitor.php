<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @since 4.2
 */
class Workflow_Fatal_Error_Monitor {

	/** @var Workflow|false */
	private static $workflow = false;


	/**
	 * Begin error monitoring
	 * @param Workflow $workflow
	 */
	static function attach( $workflow ) {
		self::$workflow = $workflow;
		add_action( 'shutdown', [ __CLASS__, 'handle_unexpected_shutdown' ] );
	}


	/**
	 * End error monitoring
	 */
	static function detach() {
		self::$workflow = false;
		remove_action( 'shutdown', [ __CLASS__, 'handle_unexpected_shutdown' ] );
	}


	static function handle_unexpected_shutdown() {
		if ( ! self::$workflow ) {
			return;
		}

		if ( $error = error_get_last() ) {
			if ( in_array( $error['type'], [ E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ] ) ) {
				self::add_error_to_workflow_log( $error );
			}
		}
	}


	/**
	 * @param array $error
	 */
	static function add_error_to_workflow_log( $error ) {
		if ( ! $log = self::$workflow->get_current_log() ) {
			return;
		}

		$log->set_has_errors( true );
		$log->add_note( sprintf( 'Unexpected shutdown: PHP Fatal error %s in %s on line %s', $error['message'], $error['file'], $error['line'] ) );
		$log->save();
	}

}

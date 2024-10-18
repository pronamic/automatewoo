<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

use AutomateWoo\Admin;
use AutomateWoo\SystemChecks\ActionSchedulerJobsRunning;
use AutomateWoo\SystemChecks\DatabaseTablesExist;

defined( 'ABSPATH' ) || exit;

/**
 * Class to run system checks periodically and add a notification if any fail
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class SystemChecks extends AbstractNotification {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-system-checks';

	/**
	 * An array of all system check classes to run.
	 *
	 * @var array
	 */
	private $system_checks;

	/**
	 * When to process this notification.
	 *
	 * @return string
	 */
	public function notification_type(): string {
		return Notifications::ACTIVATION_OR_UPDATE;
	}

	/**
	 * Get the title of the notification.
	 *
	 * @return string
	 */
	public static function get_title(): string {
		return __( 'Failed System Checks', 'automatewoo' );
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		return __( 'AutomateWoo system status check has found issues.', 'automatewoo' );
	}

	/**
	 * Return Note object.
	 *
	 * @see Automattic\WooCommerce\Admin\Notes\Note
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-system-check-details',
			__( 'View details', 'automatewoo' ),
			Admin::page_url( 'status' )
		);

		return $note;
	}

	/**
	 * Get all system checks
	 *
	 * @return array
	 */
	public function get_system_checks(): array {
		if ( ! isset( $this->system_checks ) ) {
			$this->system_checks = array_map(
				function ( $system_check ) {
					return new $system_check();
				},
				apply_filters(
					'automatewoo/system_checks',
					[
						ActionSchedulerJobsRunning::class,
						DatabaseTablesExist::class,
					]
				)
			);
		}

		return $this->system_checks;
	}

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		$checks = $this->get_system_checks();

		foreach ( $checks as $check ) {
			if ( ! $check->high_priority ) {
				continue;
			}

			$response = $check->run();
			if ( ! isset( $response['success'] ) || true !== $response['success'] ) {
				return true;
			}
		}

		return false;
	}
}

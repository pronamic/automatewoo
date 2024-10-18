<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to test if integrations are working and add a notification if they are not.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
abstract class AbstractIntegrationCheck extends AbstractNotification {
	/**
	 * When to process this notification.
	 *
	 * @return string
	 */
	public function notification_type(): string {
		return Notifications::SCHEDULED;
	}

	/**
	 * Get the title of the notification.
	 *
	 * @return string
	 */
	public static function get_title(): string {
		return __( 'AutomateWoo Integration Error', 'automatewoo' );
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		return sprintf(
			/* translators: %1$s: The Integration name. */
			__( 'Unable to communicate with the %1$s API. Please check your %1$s settings.', 'automatewoo' ),
			static::INTEGRATION_NAME
		);
	}

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		// Delete the note if it previously existed so that it can be re-added if the integration tests fails.
		$this->delete_existing_note();

		if ( ! $this->integration() instanceof Integration || ! $this->integration()->is_enabled() ) {
			return false;
		}

		return ! $this->integration()->test_integration();
	}

	/**
	 * Get instance of the Integration.
	 *
	 * @return Integration|bool
	 */
	abstract public function integration();
}

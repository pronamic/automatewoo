<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to add Inbox Notification if the minimum version of PHP supported by AutomateWoo is installed.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class PHPMinVersionCheck extends AbstractNotification {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-php-minimum-version-check';

	/**
	 * When to process this notification.
	 *
	 * @return string
	 */
	public function notification_type(): string {
		return Notifications::INSTANT;
	}

	/**
	 * Get the title of the notification.
	 *
	 * @return string
	 */
	public static function get_title(): string {
		return __( 'PHP Outdated', 'automatewoo' );
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		/* translators: Current PHP version. */
		return sprintf( __( 'AutomateWoo may drop support for the version of PHP currently running your website (%s). Please contact your web host to update to the latest version.', 'automatewoo' ), phpversion() );
	}

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return version_compare( $this->get_major_minor_version( phpversion() ), $this->get_major_minor_version( AUTOMATEWOO_MIN_PHP_VER ), '==' );
	}

	/**
	 * Return only the major and minor version number.
	 *
	 * @param string $version_number The version number to process
	 *
	 * @return string
	 */
	public function get_major_minor_version( $version_number ): string {
		if ( 2 <= substr_count( $version_number, '.' ) ) {
			$parts          = explode( '.', $version_number );
			$version_number = $parts[0] . '.' . $parts[1];
		}

		return $version_number;
	}
}

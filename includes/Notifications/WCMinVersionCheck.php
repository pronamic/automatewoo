<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to add Inbox Notification if minimum required WooCommerce version is installed.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class WCMinVersionCheck extends AbstractNotification {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-wc-minimum-version-check';

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
		return __( 'WooCommerce Outdated', 'automatewoo' );
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		return __( 'You\'re running the minimum required version of WooCommerce which AutomateWoo may eventually stop supporting. Please consider updating to the latest version.', 'automatewoo' );
	}

	/**
	 * Get Note from parent and add action directing merchants to update WooCommerce.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-update-woocommerce',
			__( 'Update WooCommerce', 'automatewoo' ),
			admin_url( 'plugins.php?s=woocommerce&plugin_status=all' )
		);

		return $note;
	}

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return version_compare( WC()->version, AUTOMATEWOO_MIN_WC_VER, '==' );
	}
}

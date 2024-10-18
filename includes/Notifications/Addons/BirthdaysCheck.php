<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to add Inbox Notification when the Birthdays add-on is activated.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class BirthdaysCheck extends AbstractAddonCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-birthdays-check';

	/**
	 * Get the addon ID
	 *
	 * @return string
	 */
	public function get_addon_id(): string {
		return 'automatewoo-birthdays';
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		return __( 'AutomateWoo Birthdays add-on was successfully activated.', 'automatewoo' );
	}

	/**
	 * Get Note from parent and add action directing merchants to Birthdays settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-addons-birthdays',
			__( 'Get Started', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=birthdays' )
		);

		return $note;
	}
}

<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use AutomateWoo\AdminNotices;
use AutomateWoo\Addons;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to test if add-on has been activated
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
abstract class AbstractAddonCheck extends AbstractNotification {
	/**
	 * Get the addon name
	 *
	 * @return string
	 */
	abstract public function get_addon_id(): string;

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
		return __( 'AutomateWoo Add-on', 'automatewoo' );
	}

	/**
	 * Get Note from parent and set the type
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );

		return $note;
	}

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return Addons::get( $this->get_addon_id() ) && in_array( 'addon_welcome_' . $this->get_addon_id(), AdminNotices::get_notices(), true );
	}
}

<?php

namespace AutomateWoo\Notifications;

use Automattic\WooCommerce\Admin\Notes\Note;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class outlining requirements for AutomateWoo Notifications.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
abstract class AbstractNotification {

	/**
	 * When the notification should be processed. Options: instant, scheduled, activate_or_update
	 *
	 * @return string
	 */
	abstract public function notification_type(): string;

	/**
	 * Get the title of the notification.
	 *
	 * @return string
	 */
	abstract public static function get_title(): string;

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	abstract public static function get_content(): string;

	/**
	 * Check if the notification should be added.
	 *
	 * @return bool
	 */
	abstract public function should_be_added(): bool;

	/**
	 * Return Note object.
	 *
	 * @see Automattic\WooCommerce\Admin\Notes\Note
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = new Note();
		$note->set_source( 'automatewoo' );
		$note->set_name( static::NOTE_NAME );
		$note->set_title( static::get_title() );
		$note->set_content( static::get_content() );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_WARNING );

		return $note;
	}

	/**
	 * Process this notification and add it if it should be added.
	 *
	 * @throws \Automattic\WooCommerce\Admin\Notes\NotesUnavailableException Throws exception when notes are unavailable.
	 *
	 * @return void
	 */
	public function process(): void {
		// AbstractNotification relies on methods from \Automattic\WooCommerce\Admin\Notes\NoteTraits.
		// If the required trait methods aren't available in classes that extend AbstractNotification then abort.
		if (
			! is_callable( static::class . '::note_exists' ) ||
			! is_callable( static::class . '::possibly_delete_note' ) ||
			! is_callable( static::class . '::can_be_added' ) ) {
			return;
		}

		$note = static::get_note();

		// If the note shouldn't be added but exists then delete it.
		if ( static::note_exists() && ! static::should_be_added() ) {
			$this->delete_existing_note();
			return;
		}

		if ( ! static::can_be_added() || ! static::should_be_added() ) {
			return;
		}

		$note->save();
	}

	/**
	 * Wrapper for \Automattic\WooCommerce\Admin\Notes\NoteTraits::note_exists().
	 *
	 * @throws \Automattic\WooCommerce\Admin\Notes\NotesUnavailableException Throws exception when notes are unavailable.
	 *
	 * @return void
	 */
	public function delete_existing_note(): void {
		static::possibly_delete_note();
	}

	/**
	 * Optional method which will run when AutomateWoo is deactivated
	 */
	public function deactivation() { }
}

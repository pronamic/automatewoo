<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Admin;
use AutomateWoo\Workflow_Query;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Add the Welcome note immediately when AutomateWoo is installed
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class WelcomeNotification extends AbstractNotification {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-welcome-notification';

	/**
	 * Option name to track if the note has ever been added.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'automatewoo_welcome_note_was_added';

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
		return __( 'AutomateWoo is ready', 'automatewoo' );
	}

	/**
	 * Get the contents of the notification.
	 *
	 * @return string
	 */
	public static function get_content(): string {
		return __( 'Create your first automated workflow easily with our presets, or build your own from scratch.', 'automatewoo' );
	}

	/**
	 * Get Note from parent and add action directing merchants to create a workflow.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-create-workflow',
			__( 'Create workflow', 'automatewoo' ),
			Admin::page_url( 'workflow-presets' )
		);

		return $note;
	}

	/**
	 * The Welcome Notification should only be displayed if it's never
	 * been added before and no workflows exist in the current install.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( get_option( self::OPTION_NAME ) ) {
			return false;
		}

		$query = new Workflow_Query();
		$query->set_limit( 1 );
		if ( empty( $query->get_results() ) ) {
			add_option( self::OPTION_NAME, true );
			return true;
		}

		return false;
	}

	/**
	 * Delete the tracking option when AutomateWoo is deactivated if the Note is still
	 * active so that the Notification can be re-added if AutomateWoo is re-activated.
	 */
	public function deactivation() {
		$note = Notes::get_note_by_name( self::NOTE_NAME );
		if ( $note && Note::E_WC_ADMIN_NOTE_UNACTIONED === $note->get_status() && ! $note->get_is_deleted() ) {
			delete_option( self::OPTION_NAME );
		}
	}
}

<?php

namespace AutomateWoo\ActivityPanelInbox;

use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Add note when updating to AW 5.4 stating that the AW Subscription add-on has been added to core.
 *
 * @since 5.4.0
 */
class SubscriptionsAddonDeactivatedNote {

	use NoteTraits;

	const NOTE_NAME = 'automatewoo-subscriptions-addon-deactivated';

	/**
	 * Init the hooks for the note.
	 */
	public function init() {
		add_action( 'automatewoo_version_changed', [ $this, 'handle_automatewoo_version_changed' ] );
		register_deactivation_hook( AUTOMATEWOO_FILE, [ __CLASS__, 'possibly_delete_note' ] );
	}

	/**
	 * Maybe add note on AW version changed action.
	 *
	 * @param string $old_version
	 */
	public function handle_automatewoo_version_changed( string $old_version ) {
		// Bail if first install or if already above 5.4
		if ( '' === $old_version || version_compare( $old_version, '5.4.0', '>=' ) ) {
			return;
		}

		// Bail if subscriptions add-on is not installed
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( ! array_key_exists( 'automatewoo-subscriptions/automatewoo-subscriptions.php', get_plugins() ) ) {
			return;
		}

		self::possibly_add_note();
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$note = new Note();
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'automatewoo' );
		$note->set_title( __( 'The AutomateWoo Subscriptions add-on has moved to AutomateWoo core', 'automatewoo' ) );
		$note->set_content(
			__(
				"To simplify using AutomateWoo with WooCommerce Subscriptions we have moved all features from the free AutomateWoo Subscriptions add-on into AutomateWoo's core plugin. The existing add-on has been deactivated and we now recommend deleting it.",
				'automatewoo'
			)
		);
		$note->add_action( 'plugins', __( 'View plugins', 'automatewoo' ), admin_url( 'plugins.php' ) );

		return $note;
	}
}

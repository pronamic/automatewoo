<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integration_Twilio;
use AutomateWoo\Integrations;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to test Twilio integration is working if enabled.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class TwilioCheck extends AbstractIntegrationCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-twilio-integration-check';

	/**
	 * Name of the integration.
	 *
	 * @var string
	 */
	const INTEGRATION_NAME = 'Twilio';

	/**
	 * Get Note from parent and add action directing merchants to Twilio settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-integrations-twilio',
			__( 'Twilio settings', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=twilio' )
		);

		return $note;
	}

	/**
	 * Get instance of the Twilio integration.
	 *
	 * @return Integration_Twilio|bool
	 */
	public function integration() {
		return Integrations::get_twilio();
	}
}

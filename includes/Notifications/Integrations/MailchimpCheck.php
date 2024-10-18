<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integrations;
use AutomateWoo\Integration_Mailchimp;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to test Mailchimp integration is working if enabled.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class MailchimpCheck extends AbstractIntegrationCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-mailchimp-integration-check';

	/**
	 * Name of the integration.
	 *
	 * @var string
	 */
	const INTEGRATION_NAME = 'Mailchimp';

	/**
	 * Get Note from parent and add action directing merchants to Mailchimp settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-integrations-mailchimp',
			__( 'Mailchimp settings', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=mailchimp' )
		);

		return $note;
	}

	/**
	 * Get instance of the Mailchimp integration.
	 *
	 * @return Integration_Mailchimp|bool
	 */
	public function integration() {
		return Integrations::mailchimp();
	}
}

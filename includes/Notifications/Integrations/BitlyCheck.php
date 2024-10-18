<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integration_Bitly;
use AutomateWoo\Integrations;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to test Bitly integration is working if enabled.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class BitlyCheck extends AbstractIntegrationCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-bitly-integration-check';

	/**
	 * Name of the integration.
	 *
	 * @var string
	 */
	const INTEGRATION_NAME = 'Bitly';

	/**
	 * Get Note from parent and add action directing merchants to Bitly settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-integrations-bitly',
			__( 'Bitly settings', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=bitly' )
		);

		return $note;
	}

	/**
	 * Get instance of the Bitly integration.
	 *
	 * @return Integration_Bitly|bool
	 */
	public function integration() {
		return Integrations::get_bitly();
	}
}

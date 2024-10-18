<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integration_ActiveCampaign;
use AutomateWoo\Integrations;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to test ActiveCampaign integration is working if enabled.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class ActiveCampaignCheck extends AbstractIntegrationCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-activecampaign-integration-check';

	/**
	 * Name of the integration.
	 *
	 * @var string
	 */
	const INTEGRATION_NAME = 'ActiveCampaign';

	/**
	 * Get Note from parent and add action directing merchants to ActiveCampaign settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-integrations-activecampaign',
			__( 'ActiveCampaign settings', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=active-campaign' )
		);

		return $note;
	}

	/**
	 * Get instance of the ActiveCampaign integration.
	 *
	 * @return Integration_ActiveCampaign|bool
	 */
	public function integration() {
		return Integrations::activecampaign();
	}
}

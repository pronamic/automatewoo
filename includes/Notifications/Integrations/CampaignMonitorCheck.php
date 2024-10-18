<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Integration_Campaign_Monitor;
use AutomateWoo\Integrations;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

defined( 'ABSPATH' ) || exit;

/**
 * Class to test Campaign Monitor integration is working if enabled.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo\Notifications
 */
class CampaignMonitorCheck extends AbstractIntegrationCheck {
	use NoteTraits;

	const NOTE_NAME = 'automatewoo-campaign-monitor-integration-check';

	/**
	 * Name of the integration.
	 *
	 * @var string
	 */
	const INTEGRATION_NAME = 'Campaign Monitor';

	/**
	 * Get Note from parent and add action directing merchants to Campaign Monitor settings.
	 *
	 * @return Note
	 */
	public static function get_note(): Note {
		$note = parent::get_note();

		$note->add_action(
			'aw-integrations-campaign-monitor',
			__( 'Campaign Monitor settings', 'automatewoo' ),
			admin_url( 'admin.php?page=automatewoo-settings&tab=campaign-monitor' )
		);

		return $note;
	}

	/**
	 * Get instance of the Campaign Monitor integration.
	 *
	 * @return Integration_Campaign_Monitor|bool
	 */
	public function integration() {
		return Integrations::campaign_monitor();
	}
}

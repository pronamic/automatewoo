<?php

namespace AutomateWoo;

use AutomateWoo\Notifications\CampaignMonitorCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_Campaign_Monitor class.
 */
class Settings_Tab_Campaign_Monitor extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'campaign-monitor';
		$this->name = __( 'Campaign Monitor', 'automatewoo' );
	}

	/**
	 * Get tab settings.
	 *
	 * @return array
	 */
	public function get_settings() {

		$tip = __( 'To find your API key or client ID, sign in to your Campaign Monitor account and click your profile image at the top right, then select Account settings, then API keys.', 'automatewoo' );

		return [
			[
				'type' => 'title',
				'id'   => 'automatewoo_campaign_monitor_integration',
				'desc' => __( 'Enabling the Campaign Monitor integration makes actions available for use when creating workflows. These actions can be used to automate adding and removing members from lists.', 'automatewoo' ),
			],
			[
				'title'    => __( 'Enable integration', 'automatewoo' ),
				'id'       => 'automatewoo_campaign_monitor_enabled',
				'autoload' => false,
				'type'     => 'checkbox',
			],
			[
				'title'    => __( 'API key', 'automatewoo' ),
				'id'       => 'automatewoo_campaign_monitor_api_key',
				'tooltip'  => $tip,
				'type'     => 'password',
				'autoload' => false,
			],
			[
				'title'    => __( 'Client ID', 'automatewoo' ),
				'id'       => 'automatewoo_campaign_monitor_client_id',
				'tooltip'  => $tip,
				'type'     => 'text',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'automatewoo_campaign_monitor_integration',
			],
		];
	}

	/**
	 * Save settings.
	 *
	 * @param array $fields Which fields to save. If empty, all fields will be saved.
	 *
	 * @return void
	 */
	public function save( $fields = array() ): void {
		Integrations::campaign_monitor()->clear_cache_data();
		parent::save();

		$campaign_monitor = Integrations::campaign_monitor();
		if ( $campaign_monitor && $campaign_monitor->test_integration() ) {
			// If a notification exists relating to a Campaign Monitor integration error, delete it.
			CampaignMonitorCheck::possibly_delete_note();
		}
	}
}

return new Settings_Tab_Campaign_Monitor();

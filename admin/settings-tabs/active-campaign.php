<?php

namespace AutomateWoo;

use AutomateWoo\Notifications\ActiveCampaignCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_Active_Campaign class.
 */
class Settings_Tab_Active_Campaign extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'active-campaign';
		$this->name = __( 'ActiveCampaign', 'automatewoo' );
	}

	/**
	 * Get tab settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return [
			[
				'type' => 'title',
				'id'   => 'automatewoo_active_campaign_integration',
			],
			[
				'title'    => __( 'Enable', 'automatewoo' ),
				'id'       => 'automatewoo_active_campaign_integration_enabled',
				'desc'     => __( 'Enable ActiveCampaign Integration', 'automatewoo' ),
				'default'  => 'no',
				'autoload' => true,
				'type'     => 'checkbox',
			],
			[
				'title'    => __( 'API URL', 'automatewoo' ),
				'id'       => 'automatewoo_active_campaign_api_url',
				'type'     => 'text',
				'autoload' => false,
			],
			[
				'title'    => __( 'API Key', 'automatewoo' ),
				'id'       => 'automatewoo_active_campaign_api_key',
				'type'     => 'password',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'automatewoo_active_campaign_integration',
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
		parent::save();

		$activecampaign = Integrations::activecampaign();
		$activecampaign->clear_cache_data();

		$this->validate_integration_on_save( $activecampaign, __( 'ActiveCampaign', 'automatewoo' ), ActiveCampaignCheck::class );
	}
}

return new Settings_Tab_Active_Campaign();

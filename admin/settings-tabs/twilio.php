<?php

namespace AutomateWoo;

use AutomateWoo\Notifications\TwilioCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_Twilio class.
 */
class Settings_Tab_Twilio extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'twilio';
		$this->name = __( 'Twilio', 'automatewoo' );
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
				'id'   => 'automatewoo_twilio_integration',
			],
			[
				'title'    => __( 'Enable', 'automatewoo' ),
				'id'       => 'automatewoo_twilio_integration_enabled',
				'desc'     => __( 'Enable Twilio Integration', 'automatewoo' ),
				'default'  => 'no',
				'autoload' => true,
				'type'     => 'checkbox',
			],
			[
				'title'    => __( 'From', 'automatewoo' ),
				'desc_tip' => __( 'Must be a Twilio phone number (in E.164 format) or alphanumeric sender ID.', 'automatewoo' ),
				'id'       => 'automatewoo_twilio_from',
				'type'     => 'text',
				'autoload' => false,
			],
			[
				'title'    => __( 'Account SID', 'automatewoo' ),
				'id'       => 'automatewoo_twilio_auth_id',
				'type'     => 'text',
				'autoload' => false,
			],
			[
				'title'    => __( 'Auth Token', 'automatewoo' ),
				'id'       => 'automatewoo_twilio_auth_token',
				'type'     => 'password',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'automatewoo_twilio_integration',
			],
		];
	}

	/**
	 * Output settings tab.
	 */
	public function output() {
		$this->output_settings_form();
		Admin::get_view( 'sms-test-twilio' );
		wp_enqueue_script( 'automatewoo-sms-test' );
	}

	/**
	 * Save settings.
	 *
	 * @param array $fields Which fields to save. If empty, all fields will be saved.
	 *
	 * @return void
	 */
	public function save( $fields = array() ): void {
		parent::save( $fields );

		$twilio = Integrations::get_twilio();
		if ( $twilio && $twilio->test_integration() ) {
			// If a notification exists relating to a Twilio integration error, delete it.
			TwilioCheck::possibly_delete_note();
		}
	}
}

return new Settings_Tab_Twilio();

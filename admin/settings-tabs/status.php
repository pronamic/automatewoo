<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_Status class.
 */
class Settings_Tab_Status extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'status';
		$this->name = __( 'Status', 'automatewoo' );
	}

	/**
	 * Output settings tab.
	 */
	public function output() {
		Admin::get_view( 'system-check' );
		$this->output_settings_form();
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
				'id'   => 'automatewoo_system_check_options',
			],
			[
				'title'    => __( 'Enable Background Checks', 'automatewoo' ),
				'id'       => 'automatewoo_enable_background_system_check',
				'desc'     => __( 'Allow occasional background checks for major system issues. If an issue is detected an admin notice will appear.', 'automatewoo' ),
				'default'  => 'yes',
				'autoload' => true,
				'type'     => 'checkbox',
			],
			[
				'type' => 'sectionend',
				'id'   => 'automatewoo_system_check_options',
			],
		];
	}
}

return new Settings_Tab_Status();

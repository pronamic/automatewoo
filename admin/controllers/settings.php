<?php

namespace AutomateWoo\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Settings
 */
class Settings extends Base {

	/** @var array */
	public $settings = [];


	/**
	 * Handle controller requests.
	 *
	 * @return void
	 */
	public function handle() {
		wp_add_inline_script(
			'automatewoo-settings',
			'var automatewooSettingsLocalizeScript = ' . wp_json_encode(
				[
					'messages' => [
						'switchToOptinWarning' => __( "WARNING: After switching to 'Opt-in mode' current customers will not be automatically opted in. Non-transactional workflows will stop sending emails and SMS until customers choose to opt-in. You can import opt-ins via AutomateWoo > Tools.", 'automatewoo' ),
					],
				]
			) . ';',
			'before'
		);

		wp_enqueue_script( 'automatewoo-settings' );

		$this->output_view(
			'page-settings',
			[
				'current_tab' => $this->get_current_tab(),
				'tabs'        => $this->get_settings_tabs(),
			]
		);
	}


	/**
	 * Save the current settings tab.
	 *
	 * @return void
	 */
	public function save() {

		// Save settings if data has been posted
		if ( empty( $_POST ) ) {
			return;
		}

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'automatewoo-settings' ) ) {
			die( esc_html__( 'Action failed. Please refresh the page and retry.', 'automatewoo' ) );
		}

		// Never save an unregistered tab into the fallback tab.
		$tab = $this->get_settings_tabs()[ $this->get_requested_tab_id() ] ?? null;

		if ( $tab ) {
			$tab->save();
		}
	}


	/**
	 * Get the current tab, falling back to the general tab if the requested one is not registered.
	 *
	 * @return \AutomateWoo\Admin_Settings_Tab_Abstract
	 */
	public function get_current_tab() {
		$tabs = $this->get_settings_tabs();

		return $tabs[ $this->get_requested_tab_id() ] ?? $tabs['general'];
	}

	/**
	 * @return string
	 */
	private function get_requested_tab_id() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin tab selection; value sanitized, no state change.
		return empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) );
	}



	/**
	 * @return array
	 */
	public function get_settings_tabs() {
		if ( empty( $this->settings ) ) {
			$path = AW()->path( '/admin/settings-tabs/' );

			$settings_includes = apply_filters(
				'automatewoo/settings/tabs',
				[
					$path . 'general.php',
					$path . 'carts.php',
					$path . 'mailchimp.php',
					$path . 'campaign-monitor.php',
					$path . 'active-campaign.php',
					$path . 'twilio.php',
					$path . 'bitly.php',
				]
			);

			$settings_includes[] = $path . 'status.php';

			foreach ( $settings_includes as $settings_include ) {
				$class = require_once $settings_include;
				if ( $class ) {
					$this->settings[ $class->id ] = $class;
				}
			}
		}

		return $this->settings;
	}
}

return new Settings();

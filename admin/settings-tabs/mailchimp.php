<?php

namespace AutomateWoo;

use AutomateWoo\Notifications\MailchimpCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Tab_Mailchimp class.
 */
class Settings_Tab_Mailchimp extends Admin_Settings_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'mailchimp';
		$this->name = __( 'MailChimp', 'automatewoo' );
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
				'id'   => 'automatewoo_mailchimp_integration',
				'desc' => __( 'Enabling the MailChimp integration does not automatically sync your data but makes three actions available when creating workflows. These actions can be used to automate how you add and remove members from your MailChimp lists and update your custom fields.', 'automatewoo' ),
			],
			[
				'title'    => __( 'Enable integration', 'automatewoo' ),
				'id'       => 'automatewoo_mailchimp_integration_enabled',
				'default'  => 'no',
				'autoload' => true,
				'type'     => 'checkbox',
			],
			[
				'title'    => __( 'API Key', 'automatewoo' ),
				'id'       => 'automatewoo_mailchimp_api_key',
				'tooltip'  => __( 'You can get your API key when logged in to MailChimp under Account > Extras > API Keys.', 'automatewoo' ),
				'type'     => 'password',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'automatewoo_mailchimp_integration',
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
		if ( ! self::validate_key() ) {
			// Invalid API key - Only save the integration enabled setting.
			parent::save( array( 'automatewoo_mailchimp_integration_enabled' ) );
		} else {
			$mailchimp = Integrations::mailchimp();

			// If a notification exists relating to a Mailchimp integration error, delete it.
			MailchimpCheck::possibly_delete_note();

			if ( $mailchimp ) {
				$mailchimp->clear_cache_data();
			}

			parent::save();
		}
	}

	/**
	 * Validate the Mailchimp API key.
	 */
	public function validate_key() {
		$api_key = aw_get_post_var( 'automatewoo_mailchimp_api_key' );

		$err_msg = __( 'The MailChimp API Key you entered is not valid.', 'automatewoo' );

		// return true if empty so users can wipe out their key.
		if ( $api_key === '' ) {
			return true;
		}

		if ( false === $api_key ) {
			parent::add_error( $err_msg );

			return false;
		}

		if ( strpos( $api_key, '-' ) === false ) {
			parent::add_error( $err_msg );

			return false;
		}

		$mailchimp_test = new Integration_Mailchimp( Clean::string( $api_key ) );

		$response = $mailchimp_test->request( 'GET', '/' );

		if ( $response->is_successful() ) {
			return true;
		} else {
			parent::add_error( $err_msg );

			return false;
		}
	}
}

return new Settings_Tab_Mailchimp();

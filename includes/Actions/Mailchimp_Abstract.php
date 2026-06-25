<?php

namespace AutomateWoo;

use AutomateWoo\Fields\Text;
use AutomateWoo\Traits\MailServiceAction;
use AutomateWoo\Traits\TagField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Abstract
 */
abstract class Action_Mailchimp_Abstract extends Action {

	use TagField;
	use MailServiceAction;

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		$this->group = __( 'MailChimp', 'automatewoo' );
	}

	/**
	 * Adds a list field selector for the current integration.
	 *
	 * @see MailServiceAction::add_integration_list_field()
	 */
	protected function add_list_field() {
		$list_field = $this->add_integration_list_field( $this->get_mailchimp_lists() );
		$this->maybe_add_mailchimp_list_field_notice( $list_field );
	}

	/**
	 * Get the Mailchimp integration, if it is configured.
	 *
	 * @since 6.6.0
	 *
	 * @return Integration_Mailchimp|false
	 */
	protected function get_mailchimp_integration() {
		return Integrations::mailchimp();
	}

	/**
	 * Get the Mailchimp integration or fail with a workflow-safe exception.
	 *
	 * @since 6.6.0
	 *
	 * @return Integration_Mailchimp
	 * @throws \Exception When the Mailchimp integration is unavailable.
	 */
	protected function mailchimp() {
		$mailchimp = $this->get_mailchimp_integration();

		if ( ! $mailchimp ) {
			throw new \Exception( esc_html( $this->get_mailchimp_settings_error_message() ) );
		}

		return $mailchimp;
	}

	/**
	 * Get Mailchimp lists for admin fields.
	 *
	 * @since 6.6.0
	 *
	 * @return array
	 */
	protected function get_mailchimp_lists() {
		$mailchimp = $this->get_mailchimp_integration();

		return $mailchimp ? $mailchimp->get_lists() : [];
	}

	/**
	 * Add an admin notice to the list field when Mailchimp lists are unavailable.
	 *
	 * @since 6.6.0
	 *
	 * @param Fields\Select $list_field The list field.
	 */
	protected function maybe_add_mailchimp_list_field_notice( $list_field ) {
		$description = '';

		if ( ! $this->get_mailchimp_integration() ) {
			$description = $this->get_mailchimp_settings_error_message();
		} elseif ( ! $list_field->get_options() ) {
			$description = __( 'No Mailchimp lists were found. Check your Mailchimp API key if this seems incorrect.', 'automatewoo' );
		}

		if ( $description ) {
			$list_field->set_description( $description );
		}
	}

	/**
	 * Get the Mailchimp settings error shown in admin fields and workflow logs.
	 *
	 * @since 6.6.0
	 *
	 * @return string
	 */
	protected function get_mailchimp_settings_error_message() {
		return __( 'Check your Mailchimp API key in AutomateWoo settings.', 'automatewoo' );
	}

	/**
	 * Add a tags field to the action.
	 *
	 * @param string $name  (Optional) The name for the tag.
	 * @param string $title (Optional) The title to display for the tag.
	 *
	 * @return Text
	 */
	protected function add_tags_field( $name = null, $title = null ) {
		$tag = $this->get_tags_field( $name, $title )
			->set_description( __( 'Add multiple tags separated by commas. Please note that tags are not case-sensitive.', 'automatewoo' ) );

		$this->add_field( $tag );

		return $tag;
	}

	/**
	 * Validate that a contact is a member of a given list.
	 *
	 * @param string $email   The email address.
	 * @param string $list_id The list ID.
	 *
	 * @throws \Exception When the contact is not valid for the list.
	 */
	protected function validate_contact( $email, $list_id ) {
		if ( ! $this->mailchimp()->is_subscribed_to_list( $email, $list_id ) ) {
			throw new \Exception( esc_html__( 'Failed because contact is not subscribed to the list.', 'automatewoo' ) );
		}
	}
}

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
		$this->add_integration_list_field( Integrations::mailchimp()->get_lists() );
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
		if ( ! Integrations::mailchimp()->is_subscribed_to_list( $email, $list_id ) ) {
			throw new \Exception( esc_html__( 'Failed because contact is not subscribed to the list.', 'automatewoo' ) );
		}
	}
}

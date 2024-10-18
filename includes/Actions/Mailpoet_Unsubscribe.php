<?php

namespace AutomateWoo;

use AutomateWoo\Actions\ActionInterface;
use AutomateWoo\Traits\MailServiceAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailpoet_Subscribe
 * @see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/UnsubscribeFromLists.md
 * @since 5.6.10
 */
class Action_Mailpoet_Unsubscribe extends Action_Mailpoet_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Remove customer from list', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 * @see MailServiceAction::add_integration_list_field()
	 * @see MailServiceAction::get_contact_email_field()
	 */
	public function load_fields() {
		$this->add_integration_list_field( Integrations::mailpoet()->get_lists() );
		$this->add_field( $this->get_contact_email_field() );
	}

	/**
	 * Implements run abstract method
	 *
	 * @see ActionInterface::run()
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$list_id = $this->get_option( 'list' );
		$email   = $this->get_contact_email_option();

		if ( ! $list_id || ! $email ) {
			return;
		}

		Integrations::mailpoet()->unsubscribe( $email, $list_id );
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\Actions\ActionInterface;
use AutomateWoo\Traits\MailServiceAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailpoet_Subscribe
 * @see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/AddSubscriber.md
 * @see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/SubscribeToList.md
 * @since 5.6.10
 */
class Action_Mailpoet_Subscribe extends Action_Mailpoet_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add customer to list', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 * @see MailServiceAction::load_subscribe_action_fields()
	 */
	public function load_fields() {
		$this->load_subscribe_action_fields( Integrations::mailpoet()->get_lists() );
	}

	/**
	 * Implements run abstract method
	 *
	 * @see ActionInterface::run()
	 */
	public function run() {
		$list_id = $this->get_option( 'list' );
		$email   = $this->get_contact_email_option();
		$opt_in  = $this->get_option( 'double_optin' );

		if ( ! $list_id || ! $email ) {
			return;
		}

		$subscriber = [
			'email'      => $email,
			'first_name' => $this->get_option( 'first_name', true ),
			'last_name'  => $this->get_option( 'last_name', true ),
		];

		Integrations::mailpoet()->subscribe( $subscriber, $list_id, [ 'send_confirmation_email' => $opt_in ] );
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\Actions\ActionInterface;
use AutomateWoo\Traits\MailServiceAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Subscribe
 */
class Action_Mailchimp_Subscribe extends Action_Mailchimp_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add Contact To List', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 * @see MailServiceAction::load_subscribe_action_fields()
	 */
	public function load_fields() {
		$this->load_subscribe_action_fields( Integrations::mailchimp()->get_lists() );
	}

	/**
	 * Implements Action run abstract method
	 *
	 * @throws \Exception When the action fails.
	 * @see ActionInterface::run()
	 */
	public function run() {
		$this->validate_required_fields();

		$list_id    = $this->get_option( 'list' );
		$email      = $this->get_contact_email_option();
		$first_name = $this->get_option( 'first_name', true );
		$last_name  = $this->get_option( 'last_name', true );

		$args            = [];
		$subscriber_hash = md5( $email );

		$args['email_address'] = $email;
		$args['status']        = $this->get_option( 'double_optin' ) ? 'pending' : 'subscribed';

		if ( $first_name || $last_name ) {
			$args['merge_fields'] = [
				'FNAME' => $first_name,
				'LNAME' => $last_name,
			];
		}

		$this->maybe_log_action( Integrations::mailchimp()->request( 'PUT', "/lists/$list_id/members/$subscriber_hash", $args ) );
	}
}

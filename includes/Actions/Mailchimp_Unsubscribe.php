<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Unsubscribe
 */
class Action_Mailchimp_Unsubscribe extends Action_Mailchimp_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Remove Contact From List', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 */
	public function load_fields() {
		$unsubscribe_only = new Fields\Checkbox();
		$unsubscribe_only->set_name( 'unsubscribe_only' );
		$unsubscribe_only->set_title( __( 'Unsubscribe only', 'automatewoo' ) );
		$unsubscribe_only->set_description( __( 'If checked the user will be unsubscribed instead of deleted.', 'automatewoo' ) );

		$this->add_list_field();
		$this->add_field( $this->get_contact_email_field() );
		$this->add_field( $unsubscribe_only );
	}

	/**
	 * Implements run abstract method.
	 *
	 * @throws \Exception When the action fails.
	 * @see ActionInterface::run()
	 */
	public function run() {
		$this->validate_required_fields();

		$list_id    = $this->get_option( 'list' );
		$email      = $this->get_contact_email_option();
		$subscriber = md5( $email );

		if ( $this->get_option( 'unsubscribe_only' ) ) {
			$this->maybe_log_action(
				Integrations::mailchimp()->request(
					'PATCH',
					"/lists/$list_id/members/$subscriber",
					[
						'status' => 'unsubscribed',
					]
				)
			);
		} else {
			$this->maybe_log_action( Integrations::mailchimp()->request( 'DELETE', "/lists/$list_id/members/$subscriber" ) );
		}
	}
}

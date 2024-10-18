<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Campaign_Monitor_Remove_Subscriber
 */
class Action_Campaign_Monitor_Remove_Subscriber extends Action_Campaign_Monitor_Abstract {

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title = __( 'Remove Subscriber From List', 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$type = new Fields\Select( false );
		$type->set_name( 'type' );
		$type->set_title( __( 'Type', 'automatewoo' ) );
		$type->set_required();
		$type->set_description( __( "If delete is selected the subscriber's email will not be added to the suppression list.", 'automatewoo' ) );
		$type->set_options(
			[
				'unsubscribe' => __( 'Unsubscribe', 'automatewoo' ),
				'delete'      => __( 'Delete', 'automatewoo' ),
			]
		);

		$this->add_field( $this->get_subscriber_email_field() );
		$this->add_field( $this->get_list_field() );
		$this->add_field( $type );
	}

	/**
	 * Run the action.
	 */
	public function run() {
		$email = Clean::email( $this->get_option( 'email', true ) );
		$list  = $this->get_option( 'list' );
		$type  = $this->get_option( 'type' );

		if ( ! $email || ! $list ) {
			return;
		}

		$api = Integrations::campaign_monitor();

		if ( $type === 'delete' ) {
			$api->request(
				'DELETE',
				"/subscribers/$list.json",
				[
					'email' => $email,
				]
			);
		} else {
			$api->request(
				'POST',
				"/subscribers/$list/unsubscribe.json",
				[
					'EmailAddress' => $email,
				]
			);
		}
	}
}

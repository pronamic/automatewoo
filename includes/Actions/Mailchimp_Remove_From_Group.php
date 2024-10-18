<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Remove_From_Group
 * @since 3.4.0
 */
class Action_Mailchimp_Remove_From_Group extends Action_Mailchimp_Add_To_Group {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Remove Contact From Group', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 */
	public function load_fields() {
		parent::load_fields();
		$this->remove_field( 'allow_add_to_list' );
	}

	/**
	 * Implements run abstract method.
	 *
	 * @throws \Exception When the action fails.
	 * @see ActionInterface::run()
	 */
	public function run() {
		$this->validate_required_fields();

		$list_id   = $this->get_option( 'list' );
		$email     = $this->get_contact_email_option();
		$interests = $this->get_option( 'groups' );

		$this->validate_contact( $email, $list_id );

		$group_updates = [];

		foreach ( $interests as $interest_id ) {
			$group_updates[ $interest_id ] = false;
		}

		$this->maybe_log_action( Integrations::mailchimp()->update_contact_interest_groups( $email, $list_id, $group_updates ) );
	}
}

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * @class Action_Active_Campaign_Create_Contact
 * @since 2.0
 */
class Action_Active_Campaign_Create_Contact extends Action_Active_Campaign_Abstract {
	/**
	 * Load admin details and set Action title/ description
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Create / Update Contact', 'automatewoo' );
		$this->description = __( 'This trigger can be used to create or update contacts in ActiveCampaign. If an existing contact is found by email then an update will occur otherwise a new contact will be created. When updating a contact any fields left blank will not be updated.', 'automatewoo' );
	}

	/**
	 * Load Action fields
	 *
	 * @return void
	 */
	public function load_fields() {
		$list_select = ( new Fields\Select() )
			->set_title( __( 'Add to list', 'automatewoo' ) )
			->set_name( 'list' )
			->set_options( Integrations::activecampaign()->get_lists() )
			->set_description( __( 'Leave blank to add a contact without assigning them to any lists.', 'automatewoo' ) );

		$this->add_contact_email_field();
		$this->add_contact_fields();
		$this->add_field( $list_select );
		$this->add_tags_field()
			->set_title( __( 'Add tags', 'automatewoo' ) );
	}


	/**
	 * Run the Action to create or update a contact on ActiveCampaign
	 *
	 * @throws Exception Thrown if there is an error when attempting to sync the contact details.
	 * @return void
	 */
	public function run() {
		$email      = Clean::email( $this->get_option( 'email', true ) );
		$first_name = $this->get_option( 'first_name', true );
		$last_name  = $this->get_option( 'last_name', true );
		$phone      = $this->get_option( 'phone', true );
		$company    = $this->get_option( 'company', true );
		$list_id    = $this->get_option( 'list' );
		$tags       = $this->parse_tags_field( $this->get_option( 'tag', true ) );

		$contact = $this->activecampaign()->sync_contact(
			$email,
			$first_name,
			$last_name,
			$phone,
			$company,
			$list_id
		);

		if ( ! $contact ) {
			throw new Exception( esc_html__( 'There was an error when attempting to create or update a contact', 'automatewoo' ) );
		}

		if ( $list_id ) {
			if ( ! $this->activecampaign()->add_contact_to_list( $contact['id'], $list_id ) ) {
				throw new Exception( esc_html__( 'There was an error when attempting to add a contact to a list', 'automatewoo' ) );
			}
		}

		if ( $tags ) {
			if ( ! $this->activecampaign()->add_tags( $contact['id'], $tags ) ) {
				throw new Exception( esc_html__( 'There was an error when attempting to add tags to the contact', 'automatewoo' ) );
			}
		}

		$this->activecampaign()->clear_contact_transients( $email );
	}
}

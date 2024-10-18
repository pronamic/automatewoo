<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * @class Action_Active_Campaign_Add_Tag
 * @since 2.0
 */
class Action_Active_Campaign_Add_Tag extends Action_Active_Campaign_Abstract {
	/**
	 * Load admin details and set Action title
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add Tags To Contact', 'automatewoo' );
	}

	/**
	 * Load Action fields
	 *
	 * @return void
	 */
	public function load_fields() {
		$create_user = ( new Fields\Checkbox() )
			->set_name( 'create_missing_contact' )
			->set_title( __( 'Create contact if missing', 'automatewoo' ) )
			->set_description( __( 'The below fields will be used only if the contact needs to be created.', 'automatewoo' ) );

		$this->add_contact_email_field();
		$this->add_tags_field()->set_required();
		$this->add_field( $create_user );
		$this->add_contact_fields();
	}

	/**
	 * Run the Action and add tags to a contact
	 *
	 * @see https://developers.activecampaign.com/reference/create-contact-tag
	 *
	 * @throws Exception Thrown if there is an error when attempting to create a new Contact or adding tags.
	 * @return void
	 */
	public function run() {
		$email                  = Clean::email( $this->get_option( 'email', true ) );
		$tags                   = $this->parse_tags_field( $this->get_option( 'tag', true ) );
		$create_missing_contact = $this->get_option( 'create_missing_contact' );

		if ( empty( $tags ) ) {
			return;
		}

		$contact = $this->activecampaign()->get_contact( $email );

		if ( ! $contact && $create_missing_contact ) {
			$first_name = $this->get_option( 'first_name', true );
			$last_name  = $this->get_option( 'last_name', true );
			$phone      = $this->get_option( 'phone', true );
			$company    = $this->get_option( 'company', true );

			$contact = $this->activecampaign()->sync_contact(
				$email,
				$first_name,
				$last_name,
				$phone,
				$company
			);
		}

		if ( ! $contact ) {
			throw new Exception( esc_html__( 'There was an error retrieving/creating the contact to add tags to', 'automatewoo' ) );
		}

		if ( ! $this->activecampaign()->add_tags( $contact['id'], $tags ) ) {
			throw new Exception( esc_html__( 'There was an error adding tags to contact', 'automatewoo' ) );
		}

		$this->activecampaign()->clear_contact_transients( $email );
	}
}

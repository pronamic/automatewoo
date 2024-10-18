<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * @class Action_Active_Campaign_Update_Contact_Field
 */
class Action_Active_Campaign_Update_Contact_Field extends Action_Active_Campaign_Abstract {
	/**
	 * Load admin details and set Action title
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Update Contact Custom Field', 'automatewoo' );
	}

	/**
	 * Load Action fields
	 *
	 * @return void
	 */
	public function load_fields() {
		$field_options = [];

		foreach ( Integrations::activecampaign()->get_contact_custom_fields() as $field ) {
			$field_options[ $field['id'] ] = $field['title'];
		}

		$field = ( new Fields\Select() )
			->set_name( 'field' )
			->set_title( __( 'Field', 'automatewoo' ) )
			->set_options( $field_options )
			->set_required();

		$value = ( new Fields\Text() )
			->set_name( 'value' )
			->set_title( __( 'Value', 'automatewoo' ) )
			->set_variable_validation();

		$this->add_contact_email_field();
		$this->add_field( $field );
		$this->add_field( $value );
	}

	/**
	 * Run the action to update a custom field for a contact
	 *
	 * @throws Exception Thrown if the given email address is not a valid contact.
	 */
	public function run(): void {
		$email    = Clean::email( $this->get_option( 'email', true ) );
		$field_id = $this->get_option( 'field' );
		$value    = $this->get_option( 'value', true );

		if ( ! $this->activecampaign()->is_contact( $email ) ) {
			throw new Exception( esc_html__( 'Failed because contact did not exist.', 'automatewoo' ) );
		}

		$data = [
			'contact' => array(
				'email'       => $email,
				'fieldValues' => array(
					array(
						'field' => $field_id,
						'value' => $value,
					),
				),
			),
		];

		$this->activecampaign()->request( 'contact/sync', $data, 'POST' )->get_body();
	}
}

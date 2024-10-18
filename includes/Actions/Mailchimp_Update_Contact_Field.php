<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Update_Contact_Field
 * @since 2.9
 */
class Action_Mailchimp_Update_Contact_Field extends Action_Mailchimp_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update List Contact Field', 'automatewoo' );
		$this->description = __( 'The contact must have been added to the list before updating any fields.', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 */
	public function load_fields() {

		$field = ( new Fields\Select() )
			->set_name( 'field' )
			->set_title( __( 'Field', 'automatewoo' ) )
			->set_required()
			->set_dynamic_options_reference( 'list' );

		$value = ( new Fields\Text() )
			->set_name( 'value' )
			->set_title( __( 'Field Value', 'automatewoo' ) )
			->set_variable_validation();

		$this->add_list_field();
		$this->add_field( $this->get_contact_email_field() );
		$this->add_field( $field );
		$this->add_field( $value );
	}


	/**
	 * TODO: Remove duplication in MailChimp_Add_To_Group::get_dynamic_field_options
	 *
	 * @param string $field_name The field name to get the options for
	 * @param bool   $reference_field_value If reference value is false, then load the last saved value, used when initially loading an action page
	 * @return array
	 */
	public function get_dynamic_field_options( $field_name, $reference_field_value = false ) {

		$options = [];
		/** @var Fields\Select $field */
		$field = $this->get_field( $field_name );

		if ( $field && $field_name !== 'field' ) {
			return [];
		}

		if ( ! $reference_field_value ) {
			$reference_field_value = $this->get_option( $field->dynamic_options_reference_field_name );
		}

		foreach ( Integrations::mailchimp()->get_list_fields( $reference_field_value ) as $field ) {
			$options[ $field['tag'] ] = "{$field['name']} - {$field['tag']}";
		}

		return $options;
	}


	/**
	 * Implements run abstract method.
	 *
	 * @throws \Exception When the action fails.
	 * @see ActionInterface::run()
	 */
	public function run() {

		$this->validate_required_fields();

		$list_id = $this->get_option( 'list' );
		$email   = $this->get_contact_email_option();
		$field   = $this->get_option( 'field' );
		$value   = $this->get_option( 'value', true );

		$this->validate_contact( $email, $list_id );

		$subscriber_hash = md5( $email );

		$args = [
			'email_address' => $email,
			'merge_fields'  => [],
		];

		$args['merge_fields'][ $field ] = $value;
		$this->maybe_log_action( Integrations::mailchimp()->request( 'PATCH', "/lists/$list_id/members/$subscriber_hash", $args ) );
	}
}

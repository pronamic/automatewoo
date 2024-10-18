<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Add_To_Group
 * @since 3.4.0
 */
class Action_Mailchimp_Add_To_Group extends Action_Mailchimp_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add Contact To Group', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 */
	public function load_fields() {

		$groups = ( new Fields\Select() )
			->set_name( 'groups' )
			->set_title( __( 'Groups', 'automatewoo' ) )
			->set_multiple()
			->set_required()
			->set_dynamic_options_reference( 'list' );

		$allow_add_to_list = ( new Fields\Checkbox() )
			->set_name( 'allow_add_to_list' )
			->set_title( __( 'Add contact to list if missing?', 'automatewoo' ) )
			->set_default_to_checked();

		$this->add_list_field();
		$this->add_field( $this->get_contact_email_field() );
		$this->add_field( $groups );
		$this->add_field( $allow_add_to_list );
	}

	/**
	 * TODO: Remove duplication in MailChimp_Update_Contact_Field::get_dynamic_field_options
	 *
	 * @param string $field_name The field name to get the options for
	 * @param bool   $reference_field_value If reference value is false, then load the last saved value, used when initially loading an action page
	 * @return array
	 */
	public function get_dynamic_field_options( $field_name, $reference_field_value = false ) {

		$options = [];
		/** @var Fields\Select $field */
		$field = $this->get_field( $field_name );

		if ( $field && $field_name !== 'groups' ) {
			return [];
		}

		if ( ! $reference_field_value ) {
			$reference_field_value = $this->get_option( $field->dynamic_options_reference_field_name );
		}

		foreach ( Integrations::mailchimp()->get_list_interest_categories( $reference_field_value ) as $interest_category ) {
			foreach ( $interest_category['interests'] as $interest_id => $interest_name ) {
				$options[ $interest_id ] = "{$interest_category['title']} - {$interest_name}";
			}
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

		$list_id           = $this->get_option( 'list' );
		$email             = $this->get_contact_email_option();
		$interests         = $this->get_option( 'groups' );
		$allow_add_to_list = $this->get_option( 'allow_add_to_list' );

		if ( ! $allow_add_to_list ) {
			$this->validate_contact( $email, $list_id );
		}

		$group_updates = [];

		foreach ( $interests as $interest_id ) {
			$group_updates[ $interest_id ] = true;
		}

		$this->maybe_log_action( Integrations::mailchimp()->update_contact_interest_groups( $email, $list_id, $group_updates ) );
	}
}

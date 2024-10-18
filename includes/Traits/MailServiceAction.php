<?php

namespace AutomateWoo\Traits;

use AutomateWoo\Clean;
use AutomateWoo\Fields\Checkbox;
use AutomateWoo\Fields\Select;
use AutomateWoo\Fields\Text;

/**
* Trait SubscribeAction
*
* Helper functions for e-mail list subscriber actions.
*
* @since 5.6.10
*/
trait MailServiceAction {

	/**
	 * Get the list field selector.
	 *
	 * @param array $lists The list array for showing in this selector.
	 * @return Select
	 */
	protected function add_integration_list_field( $lists ) {
		$list_select = ( new Select() )
			->set_title( __( 'List', 'automatewoo' ) )
			->set_name( 'list' )
			->set_options( $lists )
			->set_required();

		$this->add_field( $list_select );
		return $list_select;
	}

	/**
	 * Get the contact email field.
	 *
	 * @return Text
	 */
	protected function get_contact_email_field() {
		$field = ( new Text() )
			->set_name( 'email' )
			->set_title( __( 'Contact email', 'automatewoo' ) )
			->set_description( __( 'Use variables such as {{ customer.email }} here. If blank {{ customer.email }} will be used.', 'automatewoo' ) )
			->set_placeholder( '{{ customer.email }}' )
			->set_variable_validation();

		return $field;
	}

	/**
	 * Get the contact email option. Defaults to {{ customer.email }}.
	 *
	 * @return string|bool
	 */
	protected function get_contact_email_option() {
		$email = Clean::email( $this->get_option( 'email', true ) );

		if ( $email ) {
			return $email;
		}

		$customer = $this->workflow->data_layer()->get_customer();

		if ( ! $customer ) {
			return false;
		}

		return $customer->get_email();
	}

	/**
	 * Load the fields for a subscribe action
	 *
	 * @param array $lists The lists to add in the action list selector.
	 */
	protected function load_subscribe_action_fields( $lists ) {

		$first_name = ( new Text() )
			->set_name( 'first_name' )
			->set_title( __( 'First name', 'automatewoo' ) )
			->set_description( __( 'This field is optional.', 'automatewoo' ) )
			->set_variable_validation();

		$last_name = ( new Text() )
			->set_name( 'last_name' )
			->set_title( __( 'Last name', 'automatewoo' ) )
			->set_description( __( 'This field is optional.', 'automatewoo' ) )
			->set_variable_validation();

		$double_optin = ( new Checkbox() )
			->set_name( 'double_optin' )
			->set_title( __( 'Double optin', 'automatewoo' ) )
			->set_description( __( 'Users will receive an email asking them to confirm their subscription.', 'automatewoo' ) );

		$this->add_integration_list_field( $lists );
		$this->add_field( $this->get_contact_email_field() );
		$this->add_field( $double_optin );
		$this->add_field( $first_name );
		$this->add_field( $last_name );
	}
}

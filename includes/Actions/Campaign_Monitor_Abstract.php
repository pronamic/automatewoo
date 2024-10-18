<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Active_Campaign_Abstract
 */
abstract class Action_Campaign_Monitor_Abstract extends Action {

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->group = __( 'Campaign Monitor', 'automatewoo' );
	}


	/**
	 * @return Fields\Text
	 */
	public function get_subscriber_email_field() {
		$email = ( new Fields\Text() )
			->set_name( 'email' )
			->set_title( __( 'Subscriber email', 'automatewoo' ) )
			->set_description( __( 'You can use variables such as {{ customer.email }} here.', 'automatewoo' ) )
			->set_required()
			->set_variable_validation();
		return $email;
	}


	/**
	 * @return Fields\Text
	 */
	public function get_subscriber_name_field() {
		$email = ( new Fields\Text() )
			->set_name( 'name' )
			->set_title( __( 'Subscriber name', 'automatewoo' ) )
			->set_variable_validation();
		return $email;
	}


	/**
	 * @return Fields\Select
	 */
	public function get_list_field() {
		$list = new Fields\Select();
		$list->set_name( 'list' );
		$list->set_title( __( 'List', 'automatewoo' ) );
		$list->set_options( Integrations::campaign_monitor()->get_lists() );
		$list->set_required();
		return $list;
	}


	/**
	 * @return Fields\Checkbox
	 */
	public function get_resubscribe_field() {
		$field = new Fields\Checkbox();
		$field->set_name( 'resubscribe' );
		$field->set_title( __( 'Resubscribe', 'automatewoo' ) );
		$field->set_description( __( 'If checked the user will be subscribed even if they have already unsubscribed from one of your lists. Use with caution.', 'automatewoo' ) );
		return $field;
	}
}

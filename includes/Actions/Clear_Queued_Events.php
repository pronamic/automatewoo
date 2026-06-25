<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  @class Action_Clear_Queued_Events
 * @since 2.2
 */
class Action_Clear_Queued_Events extends Action {


	/**
	 * Load admin details for the action.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Clear Queued Events', 'automatewoo' );
		$this->group       = __( 'AutomateWoo', 'automatewoo' );
		$this->description = __( "Clears a customer's currently queued events for selected workflows.", 'automatewoo' );
	}


	/**
	 * Load the action fields.
	 */
	public function load_fields() {

		$workflows = new Fields\Workflow();
		$workflows->set_required();
		$workflows->set_title( __( 'Workflows', 'automatewoo' ) );
		$workflows->set_multiple();

		$user = new Fields\Text();
		$user->set_name( 'email' );
		$user->set_title( __( 'Customer email', 'automatewoo' ) );
		$user->set_variable_validation();
		$user->set_required();

		$this->add_field( $workflows );
		$this->add_field( $user );
	}


	/**
	 * Run the action.
	 */
	public function run() {
		$email     = Clean::email( $this->get_option( 'email', true ) );
		$workflows = $this->get_option( 'workflow' );

		if ( empty( $workflows ) || ! $email ) {
			return;
		}

		$customer = Customer_Factory::get_by_email( $email );
		if ( ! $customer ) {
			return;
		}

		$query = new Queue_Query();
		$query->where_workflow( $workflows );
		$query->where_customer_or_legacy_user( $customer, true );
		$results = $query->get_results();

		foreach ( $results as $result ) {
			$result->delete();
		}
	}
}

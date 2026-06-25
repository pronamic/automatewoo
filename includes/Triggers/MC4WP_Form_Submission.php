<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_MC4WP_Form_Submission
 * @since 3.0.0
 */
class Trigger_MC4WP_Form_Submission extends Trigger {

	/** @var array */
	public $supplied_data_items = [ 'customer' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = 'mc4wp_form_success';


	/**
	 * Load the trigger admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'MailChimp for WordPress - Form Submission', 'automatewoo' );
		$this->description = __( 'This trigger fires after a MailChimp for WordPress form is successfully submitted.', 'automatewoo' );
		$this->group       = __( 'MailChimp for WordPress', 'automatewoo' );
	}


	/**
	 * Load the trigger fields.
	 */
	public function load_fields() {
		$forms   = mc4wp_get_forms();
		$options = [];

		foreach ( $forms as $form ) {
			$options[ $form->ID ] = $form->name;
		}

		$form = ( new Fields\Select() )
			->set_title( __( 'Form', 'automatewoo' ) )
			->set_name( 'form_id' )
			->set_options( $options )
			->set_description( __( 'Choose which MailChimp for WordPress form this workflow should trigger for.', 'automatewoo' ) )
			->set_required();

		$this->add_field( $form );
	}


	/**
	 * Register the trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/mc4wp_form_success_async', [ $this, 'handle_async_event' ], 10, 2 );
	}


	/**
	 * @param int $current_form_id
	 * @param int $customer_id
	 */
	public function handle_async_event( $current_form_id, $customer_id ) {
		$current_form_id = Clean::id( $current_form_id );
		$customer_id     = Clean::id( $customer_id );

		$customer = Customer_Factory::get( $customer_id );

		foreach ( $this->get_workflows() as $workflow ) {

			$workflow_form_id = Clean::id( $workflow->get_trigger_option( 'form_id' ) );

			if ( ! $workflow_form_id || $workflow_form_id !== $current_form_id ) {
				continue;
			}

			$workflow->maybe_run(
				[
					'customer' => $customer,
				]
			);

		}
	}
}

<?php

namespace AutomateWoo;

use WC_Points_Rewards_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Action_Points_Rewards_Edit_Points_Abstract.
 *
 * An shared abstract class for manipulating customer points.
 *
 * @since   4.5.0
 * @package AutomateWoo
 */
abstract class Action_Points_Rewards_Edit_Points_Abstract extends Action {

	/**
	 * Load required data.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'customer' ];

	/**
	 * Load admin description.
	 */
	public function load_admin_details() {
		$this->group       = __( 'Customer', 'automatewoo' );
		$this->description = __( 'Please note that points are not supported on guest customers. Uses the WooCommerce Points and Rewards plugin.', 'automatewoo' );
	}

	/**
	 * Load fields for points.
	 */
	public function load_fields() {

		$points_input = new Fields\Positive_Number();

		$points_input->set_required();
		$points_input->set_name( 'points' );
		$points_input->set_title( __( 'Number of Points', 'automatewoo' ) );

		$this->add_field( $points_input );

		$log_label = new Fields\Text();
		$log_label->set_name( 'adjustment_description' );
		$log_label->set_title( __( 'Event Description', 'automatewoo' ) );
		$log_label->set_placeholder( Points_Rewards_Integration::get_default_event_description() );
		$log_label->set_description( __( 'Describe the event and/or why the points were modified. This description will be visible to customers on their "my account" points log.', 'automatewoo' ) );

		$this->add_field( $log_label );
	}

	/**
	 * Modify Points
	 *
	 * @param string $action
	 */
	public function modify_points( $action ) {

		$event_type = 'automatewoo-adjustment';
		$customer   = $this->workflow->data_layer()->get_customer();
		$points     = $this->get_option( 'points' );

		if ( ! $customer || ! $customer->is_registered() || empty( $points ) || empty( $action ) ) {
			return;
		}

		$adjustment_description = $this->get_option( 'adjustment_description' );

		$data = [
			'workflow_id'    => $this->workflow->get_id(),
			'aw_description' => $adjustment_description,
		];

		switch ( $action ) {
			case 'add':
				WC_Points_Rewards_Manager::increase_points( $customer->get_user_id(), $points, $event_type, $data );
				break;
			case 'remove':
				WC_Points_Rewards_Manager::decrease_points( $customer->get_user_id(), $points, $event_type, $data );
				break;
		}
	}
}

<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Abstract_Memberships
 * @since 2.8.3
 */
abstract class Trigger_Abstract_Memberships extends Trigger {

	/** @var array */
	public $supplied_data_items = [ 'membership', 'customer' ];

	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		$this->group = __( 'Memberships', 'automatewoo' );
	}


	/**
	 * @return Fields\Select
	 */
	public function get_field_membership_plans() {

		$plans = Memberships_Helper::get_membership_plans();

		return ( new Fields\Select() )
			->set_name( 'membership_plans' )
			->set_title( __( 'Membership plans', 'automatewoo' ) )
			->set_placeholder( __( 'Select which membership plans to trigger for. Leave blank to apply for all plans.', 'automatewoo' ) )
			->set_options( $plans )
			->set_multiple();
	}
}

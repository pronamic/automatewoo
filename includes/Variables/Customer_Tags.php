<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Tags
 */
class Variable_Customer_Tags extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays a comma separated list of the customer's user tags.", 'automatewoo' );
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		$tags      = wp_get_object_terms( $customer->get_user_id(), 'user_tag' );
		$tag_names = wp_list_pluck( $tags, 'name' );
		return implode( ', ', $tag_names );
	}
}

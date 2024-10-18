<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_Tags
 */
class Variable_Customer_Tags extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a comma separated list of the customer's user tags.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		$tags = wp_get_object_terms( $customer->get_user_id(), 'user_tag' );
		$tag_names = wp_list_pluck( $tags, 'name' );
		return implode( ', ', $tag_names );
	}

}

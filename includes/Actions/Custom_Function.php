<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Custom_Function
 */
class Action_Custom_Function extends Action {


	function load_admin_details() {
		$this->title       = __( 'Custom Function', 'automatewoo' );
		$this->description = sprintf(
			__( 'This action can be used by developers to trigger custom code from a workflow. <%s>View documentation<%s>.', 'automatewoo' ),
			'a href="' . Admin::get_docs_link( 'actions/custom-functions/' ) . '"',
			'/a'
		);
	}


	function load_fields() {
		$function_name = new Fields\Text();
		$function_name->set_title( __( 'Function name', 'automatewoo'  ) );
		$function_name->set_name('function_name');

		$this->add_field($function_name);
	}


	function run() {
		$function = $this->get_option( 'function_name' );
		if ( function_exists( $function ) ) {
			call_user_func( $function, $this->workflow );
		}
	}

}

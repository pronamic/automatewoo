<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Note_Content
 */
class Variable_Order_Note_Content extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the content of the order note.", 'automatewoo');
	}


	/**
	 * @param $comment Order_Note
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $comment, $parameters ) {
		return $comment->content;
	}
}

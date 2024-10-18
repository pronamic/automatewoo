<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Comment_ID
 */
class Variable_Comment_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the ID of the comment.", 'automatewoo');
	}


	/**
	 * @param $comment \WP_Comment
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $comment, $parameters ) {
		return $comment->comment_ID;
	}
}

<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Comment_Author_IP
 */
class Variable_Comment_Author_IP extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the IP address of the comment author.", 'automatewoo');
	}


	/**
	 * @param $comment \WP_Comment
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $comment, $parameters ) {
		return $comment->comment_author_IP;
	}
}

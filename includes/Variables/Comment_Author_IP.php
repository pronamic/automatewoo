<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Comment_Author_IP
 */
class Variable_Comment_Author_IP extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the IP address of the comment author.', 'automatewoo' );
	}

	/**
	 * @param \WP_Comment $comment
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $comment, $parameters ) {
		return $comment->comment_author_IP;
	}
}

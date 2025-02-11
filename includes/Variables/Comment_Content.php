<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Comment_Content
 */
class Variable_Comment_Content extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the content of the comment.', 'automatewoo' );
	}

	/**
	 * @param \WP_Comment $comment
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $comment, $parameters ) {
		return $comment->comment_content;
	}
}

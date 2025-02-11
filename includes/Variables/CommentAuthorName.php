<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use WP_Comment;

defined( 'ABSPATH' ) || exit;

/**
 * CommentAuthorName class
 */
class CommentAuthorName extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the name of the comment author.', 'automatewoo' );
	}

	/**
	 * @param WP_Comment $comment
	 * @param array      $parameters
	 *
	 * @return string
	 */
	public function get_value( $comment, $parameters ) {
		return $comment->comment_author;
	}
}

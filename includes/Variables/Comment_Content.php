<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Comment_Content
 */
class Variable_Comment_Content extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the content of the comment.", 'automatewoo');
	}


	/**
	 * @param $comment \WP_Comment
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $comment, $parameters ) {
		return $comment->comment_content;
	}

}

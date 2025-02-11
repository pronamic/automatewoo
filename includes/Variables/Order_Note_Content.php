<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Note_Content
 */
class Variable_Order_Note_Content extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the content of the order note.', 'automatewoo' );
	}

	/**
	 * @param \Order_Note $comment
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $comment, $parameters ) {
		return $comment->content;
	}
}

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Card_Last4
 */
class Variable_Card_Last4 extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the last 4 digits of the card.', 'automatewoo' );
	}

	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param array                $parameters
	 * @return string
	 */
	public function get_value( $card, $parameters ) {
		return $card->get_last4();
	}
}

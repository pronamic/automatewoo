<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Card_Expiry_Year
 */
class Variable_Card_Expiry_Year extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the expiry year of the card.', 'automatewoo' );
	}

	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param array                $parameters
	 * @return string
	 */
	public function get_value( $card, $parameters ) {
		return $card->get_expiry_year();
	}
}

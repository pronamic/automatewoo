<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Card_Type
 */
class Variable_Card_Type extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the type of the card e.g. Visa, MasterCard.', 'automatewoo' );
	}

	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param array                $parameters
	 * @return string
	 */
	public function get_value( $card, $parameters ) {
		return wc_get_credit_card_type_label( $card->get_card_type() );
	}
}

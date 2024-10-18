<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Card_Type
 */
class Variable_Card_Type extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the type of the card e.g. Visa, MasterCard.", 'automatewoo');
	}


	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $card, $parameters ) {
		return wc_get_credit_card_type_label( $card->get_card_type() );
	}
}

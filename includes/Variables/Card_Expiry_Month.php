<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Card_Expiry_Month
 */
class Variable_Card_Expiry_Month extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the expiry month of the card.", 'automatewoo');
	}


	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $card, $parameters ) {
		return $card->get_expiry_month();
	}
}

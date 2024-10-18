<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Card_Last4
 */
class Variable_Card_Last4 extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the last 4 digits of the card.", 'automatewoo');
	}


	/**
	 * @param \WC_Payment_Token_CC $card
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $card, $parameters ) {
		return $card->get_last4();
	}
}

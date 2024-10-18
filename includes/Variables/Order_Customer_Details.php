<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Customer_Details
 */
class Variable_Order_Customer_Details extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the HTML formatted customer details that are normally shown at the bottom of order transactional emails.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {
		WC()->mailer();
		ob_start();
		do_action( 'woocommerce_email_customer_details', $order, false, false, '' );
		return ob_get_clean();
	}
}

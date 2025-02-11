<?php

namespace AutomateWoo;

use AutomateWoo\Frontend_Endpoints\Login_Redirect;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_View_Url
 */
class Variable_Order_View_Url extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a URL to view the order in the user account area.', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return ( new Login_Redirect() )->get_login_redirect_url( $order->get_view_order_url() );
	}
}

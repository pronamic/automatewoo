<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling Cart Link Variable
 *
 * @class Variable_Cart_Link
 */
class Variable_Cart_Link extends Variable {


	/**
	 * Load the admin details for this variable
	 */
	public function load_admin_details() {
		$this->description = __( "Displays a unique link to the cart page that will also restore items to the customer's cart.", 'automatewoo' );
		$this->add_parameter_select_field(
			'page',
			__( 'Sets which page the link will direct the customer to when clicked.', 'automatewoo' ),
			[
				''         => __( 'Cart', 'automatewoo' ),
				'checkout' => __( 'Checkout', 'automatewoo' ),
			]
		);
	}


	/**
	 * Get the Cart Link URL
	 *
	 * @param Cart  $cart The cart object
	 * @param array $parameters The variable parameters
	 * @return string The Cart Link URL
	 */
	public function get_value( $cart, $parameters ) {
		$page = empty( $parameters['page'] ) ? 'cart' : $parameters['page'];

		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw(
			add_query_arg(
				[
					'aw-action' => 'restore-cart',
					'token'     => $cart->get_token(),
					'redirect'  => $page,
				],
				wc_get_page_permalink( 'cart' )
			)
		);
	}
}

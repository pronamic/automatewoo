<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Payment_Gateway
 */
class Payment_Gateway extends Select {

	protected $name = 'payment_gateway';

	public $multiple = true;


	/**
	 * @param bool $show_placeholder
	 */
	function __construct( $show_placeholder = true ) {
		parent::__construct( $show_placeholder );

		$this->set_title( __( 'Payment Gateway', 'automatewoo' ) );
		$this->set_description( __( 'Only trigger when the order is placed with certain payment methods.', 'automatewoo'  ) );

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway->enabled === 'yes') {
				$this->options[$gateway->id] = $gateway->get_title();
			}
		}
	}

}

<?php
namespace AutomateWoo;

use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imitates WP_User object but ID is always 0
 * This object should be used as a data-type 'user' and can be queued with an order
 *
 * @class Order_Guest
 * @since 2.1.0
 * @deprecated since 3.0, use Customer instead
 */
class Order_Guest {

	/** @var int */
	public $ID = 0;

	/** @var string */
	public $user_email;

	/** @var string */
	public $first_name;

	/** @var string */
	public $last_name;

	/** @var string */
	public $billing_phone;

	/** @var string */
	public $billing_country;

	/** @var string */
	public $billing_postcode;

	/** @var string */
	public $billing_state;

	/** @var string */
	public $billing_city;

	/** @var string */
	public $shipping_country;

	/** @var string */
	public $shipping_state;

	/** @var string */
	public $shipping_city;

	/** @var string */
	public $shipping_postcode;

	/** @var WC_Order */
	public $order;

	/** @var array  */
	public $roles = [ 'guest' ];


	/**
	 * @param WC_Order|bool $order Existing order.
	 */
	public function __construct( $order = false ) {
		if ( $order ) {

			$this->order = $order;

			$this->user_email        = $order->get_billing_email();
			$this->first_name        = $order->get_billing_first_name();
			$this->last_name         = $order->get_billing_last_name();
			$this->billing_phone     = $order->get_billing_phone();
			$this->billing_country   = $order->get_billing_country();
			$this->billing_city      = $order->get_billing_city();
			$this->billing_state     = $order->get_billing_state();
			$this->billing_postcode  = $order->get_billing_postcode();
			$this->shipping_country  = $order->get_shipping_country();
			$this->shipping_city     = $order->get_shipping_city();
			$this->shipping_state    = $order->get_shipping_state();
			$this->shipping_postcode = $order->get_shipping_postcode();
		}
	}
}

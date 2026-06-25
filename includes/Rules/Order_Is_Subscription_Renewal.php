<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Is_Subscription_Renewal
 * @since 2.9
 */
class Order_Is_Subscription_Renewal extends Abstract_Bool {

	/**
	 * @var string
	 */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Is Subscription Renewal', 'automatewoo' );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		$is_renewal = wcs_order_contains_renewal( $order );

		switch ( $value ) {
			case 'yes':
				return $is_renewal;

			case 'no':
				return ! $is_renewal;
		}
	}
}

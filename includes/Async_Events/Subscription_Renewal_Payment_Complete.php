<?php

namespace AutomateWoo\Async_Events;

use WC_Order;
use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Renewal_Payment_Complete
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Renewal_Payment_Complete extends Abstract_Async_Event {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'woocommerce_subscription_renewal_payment_complete', [ $this, 'schedule_event' ], 20, 2 );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/subscription/renewal_payment_complete_async';
	}

	/**
	 * Schedule event.
	 *
	 * @param WC_Subscription $subscription
	 * @param WC_Order        $order
	 */
	public function schedule_event( $subscription, $order ) {
		$this->create_async_event( [ $subscription->get_id(), $order->get_id() ] );
	}
}

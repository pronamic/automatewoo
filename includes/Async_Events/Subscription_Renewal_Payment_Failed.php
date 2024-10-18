<?php

namespace AutomateWoo\Async_Events;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Renewal_Payment_Failed
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Renewal_Payment_Failed extends Subscription_Renewal_Payment_Complete {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'woocommerce_subscription_renewal_payment_failed', [ $this, 'schedule_event' ], 20, 2 );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/subscription/renewal_payment_failed_async';
	}
}

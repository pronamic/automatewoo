<?php

namespace AutomateWoo\Triggers\Utilities;

/**
 * Trait SubscriptionGroup
 *
 * Sets the trigger in the 'Subscriptions' group.
 *
 * @since 5.2.0
 */
trait SubscriptionGroup {

	/**
	 * @return string
	 */
	public function get_group() {
		return __( 'Subscriptions', 'automatewoo' );
	}
}

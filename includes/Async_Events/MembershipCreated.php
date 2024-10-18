<?php

namespace AutomateWoo\Async_Events;

defined( 'ABSPATH' ) || exit;

/**
 * Class MembershipCreated
 *
 * @since 5.2.0
 */
class MembershipCreated extends Abstract_Async_Event {

	const NAME = 'membership_created';

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'wc_memberships_user_membership_created', [ $this, 'handle_membership_created' ], 10, 2 );
	}

	/**
	 * Handle membership created action.
	 *
	 * @param \WC_Memberships_Membership_Plan $plan
	 * @param array                           $args
	 */
	public function handle_membership_created( $plan, $args ) {
		if ( $args['is_update'] ) {
			// The membership is being updated.
			return;
		}

		$this->create_async_event( [ (int) $args['user_membership_id'] ] );
	}
}

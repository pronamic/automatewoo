<?php

namespace AutomateWoo\ActionScheduler;

use ActionScheduler_AsyncRequest_QueueRunner;

defined( 'ABSPATH' ) || exit;

/**
 * Class AW_AsyncRequest_QueueRunner
 *
 * Extends ActionScheduler_AsyncRequest_QueueRunner to remove cookies from post args.
 * - By default, Async ActionScheduler requests will sleep for 5 seconds to avoid chaining requests too frequently.
 * - If cookies are included in the request then core WooCommerce shutdown actions, such as storing session data,
 *   will be triggered after that 5 second delay and then repeated if another request is dispatched.
 *
 * This can lead to race conditions and unexpected behaviour such as the cart being recreated after checkout is
 * complete or the cart being cleared when it shouldn't be. Removing cookies entirely from the AutomateWoo Async
 * Queue Runner will prevent that from happening and ensure that only the actions we want to run are executed.
 *
 * @see ActionScheduler_AsyncRequest_QueueRunner
 *
 * @since 5.7.5
 */
class AW_AsyncRequest_QueueRunner extends ActionScheduler_AsyncRequest_QueueRunner {
	/**
	 * Get post args for the request
	 *
	 * @return array
	 */
	protected function get_post_args() {
		return array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => $this->data,
			'cookies'   => array(),
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);
	}
}

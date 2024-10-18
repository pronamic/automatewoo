<?php

namespace AutomateWoo\Actions;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Interface TestableInterface
 *
 * @since 5.2.0
 */
interface TestableInterface extends ActionInterface {

	/**
	 * Run the action as a test.
	 *
	 * @param array $args Optionally add args for the test.
	 *
	 * @return true|WP_Error
	 */
	public function run_test( array $args = [] );
}

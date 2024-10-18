<?php

namespace AutomateWoo\Actions;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Interface PreviewableInterface
 *
 * @since 5.2.0
 */
interface PreviewableInterface extends ActionInterface {

	/**
	 * Returns preview content.
	 *
	 * E.g. the email preview HTML.
	 *
	 * @return string|WP_Error
	 */
	public function get_preview();
}

<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\AdminNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractAdminNotice
 *
 * @since 5.1.3
 */
abstract class AbstractAdminNotice implements AdminNoticeInterface {

	/**
	 * Get the unique notice ID.
	 *
	 * @return string
	 */
	abstract protected function get_id(): string;

	/**
	 * Init the notice, add hooks.
	 */
	public function init() {
		/**
		 * Add notice output hook.
		 *
		 * @see \AutomateWoo\AdminNotices::output_notices
		 */
		add_action( 'automatewoo/admin_notice/' . $this->get_id(), [ $this, 'output' ] );
	}

	/**
	 * Adds the notice to the current admin notices.
	 */
	protected function add_notice() {
		AdminNotices::add_notice( $this->get_id() );
	}
}

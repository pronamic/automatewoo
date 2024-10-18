<?php

namespace AutomateWoo\AdminNotices;

/**
 * Interface AdminNoticeInterface
 *
 * @since 5.1.3
 */
interface AdminNoticeInterface {

	/**
	 * Init the notice, add hooks.
	 */
	public function init();

	/**
	 * Output/render the notice HTML.
	 */
	public function output();
}

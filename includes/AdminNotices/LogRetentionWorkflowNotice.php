<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\Admin;
use AutomateWoo\AdminNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Displays a warning notice on the workflow editor screen when log retention
 * is enabled.
 *
 * When old logs are purged, workflows that rely on run-count rules (e.g.
 * "customer run count equals 1" for once-per-customer logic) may re-trigger
 * for customers whose previous runs have been deleted.
 *
 * @since 6.3.2
 */
class LogRetentionWorkflowNotice extends AbstractAdminNotice {

	/**
	 * Get the unique notice ID.
	 *
	 * @since 6.3.2
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'log_retention_workflow_warning';
	}

	/**
	 * Initialize the notice.
	 *
	 * @since 6.3.2
	 */
	public function init() {
		parent::init();
		add_action( 'current_screen', [ $this, 'maybe_add_notice' ] );
	}

	/**
	 * Conditionally add the notice on workflow edit screens when retention is active.
	 *
	 * @since 6.3.2
	 */
	public function maybe_add_notice(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'aw_workflow' !== $screen->id ) {
			return;
		}

		$retention_months = \AW()->options_store()->get_log_retention_months();

		if ( $retention_months <= 0 ) {
			return;
		}

		// Only persist when the notice isn't already stored to avoid an unnecessary update_option() on every page load.
		if ( ! in_array( $this->get_id(), AdminNotices::get_notices(), true ) ) {
			$this->add_notice();
		}
	}

	/**
	 * Output the notice HTML.
	 *
	 * @since 6.3.2
	 */
	public function output() {
		$retention_months = \AW()->options_store()->get_log_retention_months();

		if ( $retention_months <= 0 ) {
			// Retention was disabled since the notice was added. Clean up.
			AdminNotices::remove_notice( $this->get_id() );
			return;
		}

		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => $this->get_id(),
				'type'              => 'warning',
				'strong'            => sprintf(
					/* translators: %d: number of months */
					__( 'Log retention is enabled (%d months).', 'automatewoo' ),
					$retention_months
				),
				'message'           => __( 'Workflows using run-count rules (e.g. "Customer run count") may re-trigger for customers after their old logs are deleted.', 'automatewoo' ),
			]
		);
	}
}

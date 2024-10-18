<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\Admin;
use AutomateWoo\AdminNotices;

/**
 * Queue and display the dismissible New Workflow Helper notice on the Add New Workflow page for
 * first installs or upgrades from <5.0 to >=5.1
 *
 * @since   5.1.0
 * @package AutomateWoo\AdminNotices
 */
class NewWorkflowHelperManager {

	/** @var string */
	const NOTICE_ID = 'new_workflow_helper';

	/** @var string */
	const VERSION_PRESETS_INTRODUCED = '5.1';

	/**
	 * Attach callbacks.
	 */
	public static function init() {
		add_action( 'automatewoo_first_installed', [ __CLASS__, 'maybe_add_new_workflow_helper_notice' ] );
		add_action( 'automatewoo_version_changed', [ __CLASS__, 'maybe_add_new_workflow_helper_notice' ] );

		add_action( 'automatewoo/admin_notice/' . self::NOTICE_ID, [ __CLASS__, 'output_new_workflow_helper_notice' ] );
	}

	/**
	 * Output New Workflow Helper notice ONLY on the Add New Workflow page.
	 */
	public static function output_new_workflow_helper_notice() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'aw_workflow' || $screen->action !== 'add' ) {
			return;
		}

		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => self::NOTICE_ID,
				'type'              => 'info',
				'class'             => 'is-dismissible automatewoo-notice--new-workflow-helper',
				'strong'            => '',
				'message'           => sprintf(
					/* translators: %1$s: workflow presets opening link tag, %2$s: closing link tag, %3$s: help opening link tag, %4$s: closing link tag */
					__( 'Need help? Try out one of our <%1$s>preset workflows<%2$s> or check out the <%3$s>help center<%4$s>.', 'automatewoo' ),
					'a href="' . Admin::page_url( 'workflow-presets' ) . '" target="_blank" data-automatewoo-link-type="presets"',
					'/a',
					'a href="' . Admin::get_docs_link( '', 'new-workflow-helper-notice' ) . '" target="_blank" data-automatewoo-link-type="docs"',
					'/a'
				),
			]
		);
	}


	/**
	 * Add the New Workflow Helper notice on first install or upgrade from <5.1 to >=5.1
	 *
	 * @param string|null $old_version the previously installed version for updates (or null for new installs)
	 * @param string|null $new_version the new version
	 */
	public static function maybe_add_new_workflow_helper_notice( $old_version = null, $new_version = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$is_first_install      = ! $old_version;
		$is_applicable_upgrade = version_compare( $old_version, self::VERSION_PRESETS_INTRODUCED, '<' );
		if ( $is_first_install || $is_applicable_upgrade ) {
			AdminNotices::add_notice( 'new_workflow_helper' );
		}
	}
}

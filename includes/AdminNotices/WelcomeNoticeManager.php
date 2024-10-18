<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\AdminNotices;
use AutomateWoo\Workflow_Query;
use AutomateWoo\Admin;

/**
 * Display an admin notice on plugin update
 *
 * @since   5.1.0
 * @package AutomateWoo
 */
class WelcomeNoticeManager {
	const NOTICE_ID = 'welcome';

	/**
	 * Attach callbacks.
	 */
	public static function init() {
		add_action( 'automatewoo_first_installed', [ __CLASS__, 'add_admin_notice' ], 10, 2 );
		add_action( 'automatewoo/admin_notice/' . self::NOTICE_ID, [ __CLASS__, 'output_admin_notice' ] );
	}

	/**
	 * Add a welcome admin notice - generally to be called when the plugin is installed for the first time.
	 */
	public static function add_admin_notice() {
		AdminNotices::add_notice( 'welcome' );
	}

	/**
	 * Determines if the notice should be removed
	 *
	 * @return bool True if there are any Workflows created and hence the notice is going to be removed
	 */
	public static function maybe_remove_notice() {
		$query = new Workflow_Query();
		$query->set_limit( 1 );

		if ( count( $query->get_results() ) > 0 ) {
			AdminNotices::remove_notice( 'welcome' );
			return true;
		}

		return false;
	}

	/**
	 * Outputs the update notice including details about the update.
	 */
	public static function output_admin_notice() {

		if ( self::maybe_remove_notice() ) {
			return;
		}

		$title       = __( 'Welcome to AutomateWoo!', 'automatewoo' );
		$description = __( 'Create your first workflow easily with our presets, or build your own from scratch.', 'automatewoo' );
		$links       = [
			[
				'text'           => __( 'Browse presets', 'automatewoo' ),
				'href'           => Admin::page_url( 'workflow-presets' ),
				'class'          => 'button-primary',
				'data_link_type' => 'presets',
				'target'         => '_self',
			],
			[
				'text'           => __( 'Learn more', 'automatewoo' ),
				'href'           => Admin::get_docs_link( 'getting-started', 'welcome-notice' ),
				'class'          => 'button-secondary',
				'data_link_type' => 'getting_started',
				'target'         => '_blank',
			],
		];

		Admin::get_view(
			'welcome-notice',
			[
				'notice_identifier' => self::NOTICE_ID,
				'title'             => $title,
				'description'       => $description,
				'links'             => $links,
			]
		);
	}
}

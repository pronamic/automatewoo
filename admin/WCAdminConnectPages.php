<?php

namespace AutomateWoo\Admin;

use AutomateWoo\Admin;
use AutomateWoo\Clean;
use AutomateWoo\Options;

/**
 * Class to enable the WooCommerce Admin (bar, breadcrumbs, notifications, etc.).
 *
 * @since   5.0.0
 * @package AutomateWoo\Admin
 */
class WCAdminConnectPages {

	/**
	 * The first breadcrumb item.
	 *
	 * @var string
	 */
	const BREADCRUMB_ROOT = 'automatewoo-dashboard';

	/**
	 * Initialize the class and add hook callbacks.
	 */
	public function init() {
		if ( ! WC()->is_wc_admin_active() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_automatewoo_admin_pages' ] );
		add_action( 'admin_menu', [ $this, 'register_automatewoo_tabbed_pages' ] );
		add_action( 'current_screen', [ $this, 'register_automatewoo_tools_pages' ] );
	}

	/**
	 * Connects basic Automatewoo admin pages to WooCommerce Admin.
	 */
	public function register_automatewoo_admin_pages() {

		// Remove the WooCommerce base node from the breadcrumbs for AutomateWoo pages.
		add_filter(
			'woocommerce_navigation_get_breadcrumbs',
			function ( $breadcrumbs ) {
				if ( Admin::is_automatewoo_screen() ) {
					array_shift( $breadcrumbs );
				}
				return $breadcrumbs;
			}
		);

		// AutomateWoo AND AutomateWoo > Dashboard.
		wc_admin_connect_page(
			[
				'id'        => self::BREADCRUMB_ROOT,
				'screen_id' => 'automatewoo_page_automatewoo-dashboard',
				'title'     => [
					__( 'AutomateWoo', 'automatewoo' ),
					__( 'Dashboard', 'automatewoo' ),
				],
				'path'      => add_query_arg( 'page', 'automatewoo-dashboard', 'admin.php' ),
			]
		);

		// AutomateWoo > Workflows.
		wc_admin_connect_page(
			[
				'id'        => 'automatewoo-workflows',
				'parent'    => self::BREADCRUMB_ROOT,
				'screen_id' => 'edit-aw_workflow',
				'title'     => __( 'Workflows', 'automatewoo' ),
				'path'      => add_query_arg( 'post_type', 'aw_workflow', 'edit.php' ),
			]
		);

		// AutomateWoo > Workflows > Add New Workflow.
		wc_admin_connect_page(
			[
				'id'        => 'automatewoo-add-workflow',
				'parent'    => 'automatewoo-workflows',
				'screen_id' => 'aw_workflow-add',
				'title'     => __( 'Add New Workflow', 'automatewoo' ),
			]
		);

		// AutomateWoo > Workflows > Edit Workflow.
		wc_admin_connect_page(
			[
				'id'        => 'automatewoo-edit-workflow',
				'parent'    => 'automatewoo-workflows',
				'screen_id' => 'aw_workflow',
				'title'     => __( 'Edit Workflow', 'automatewoo' ),
			]
		);

		// Simple pages: AutomateWoo > [Page].
		$simple_pages = [
			'logs'    => __( 'Logs', 'automatewoo' ),
			'queue'   => __( 'Queue', 'automatewoo' ),
			'carts'   => __( 'Carts', 'automatewoo' ),
			'guests'  => __( 'Guests', 'automatewoo' ),
			'opt-ins' => Options::optin_enabled() ? __( 'Opt-ins', 'automatewoo' ) : __( 'Opt-outs', 'automatewoo' ),
		];
		foreach ( $simple_pages as $screen_id => $title ) {
			wc_admin_connect_page(
				[
					'id'        => 'automatewoo-' . $screen_id,
					'parent'    => self::BREADCRUMB_ROOT,
					'screen_id' => 'automatewoo_page_automatewoo-' . $screen_id,
					'title'     => $title,
				]
			);
		}
	}

	/**
	 * Tabbed pages can be handled by WooCommerce Admin if registered with WC first.
	 * AW Settings and Reports pages can use the tab values directly.
	 * https://github.com/woocommerce/woocommerce-admin/blob/v1.2.3/docs/page-controller.md#determining-screen-id
	 */
	public function register_automatewoo_tabbed_pages() {
		add_filter(
			'woocommerce_navigation_pages_with_tabs',
			function ( $navigation_pages ) {
				return array_merge(
					$navigation_pages,
					[
						'automatewoo-settings' => 'general',
						'automatewoo-reports'  => 'runs-by-date',
					]
				);
			}
		);

		// AutomateWoo > Settings > [Tab].
		/** @var Admin\Controllers\Settings $settings */
		$settings      = Admin\Controllers::get( 'settings' );
		$settings_path = add_query_arg( [ 'page' => 'automatewoo-settings' ], 'admin.php' );
		foreach ( $settings->get_settings_tabs() as $screen_id => $setting_object ) {
			wc_admin_connect_page(
				[
					'id'        => 'automatewoo-settings-' . $setting_object->id,
					'parent'    => self::BREADCRUMB_ROOT,
					'screen_id' => 'automatewoo_page_automatewoo-settings-' . $setting_object->id,
					'title'     => [
						__( 'Settings', 'automatewoo' ),
						$setting_object->name,
					],
					'path'      => $settings_path,
				]
			);
		}

		// AutomateWoo > Reports > [Tab].
		/** @var Admin\Controllers\Reports $reports */
		$reports      = Admin\Controllers::get( 'reports' );
		$reports_path = add_query_arg( [ 'page' => 'automatewoo-reports' ], 'admin.php' );
		foreach ( $reports->get_reports_tabs() as $screen_id => $report_object ) {
			wc_admin_connect_page(
				[
					'id'        => 'automatewoo-reports-' . $report_object->id,
					'parent'    => self::BREADCRUMB_ROOT,
					'screen_id' => 'automatewoo_page_automatewoo-reports-' . $report_object->id,
					'title'     => [
						__( 'Reports', 'automatewoo' ),
						$report_object->name,
					],
					'path'      => $reports_path,
				]
			);
		}
	}

	/**
	 * Enable "Tools" WC Admin breadcrumbs on the fly.
	 * All the tools share a screen_id and don't use the "tab" query parameter.
	 */
	public function register_automatewoo_tools_pages() {
		if ( Admin::get_screen_id() === 'tools' ) {
			// Basic info for root "Tools" page.
			$page_info = [
				'id'        => 'automatewoo-tools',
				'parent'    => self::BREADCRUMB_ROOT,
				'screen_id' => 'automatewoo_page_automatewoo-tools',
				'title'     => __( 'Tools', 'automatewoo' ),
			];

			$tool_id = Clean::string( aw_request( 'tool_id' ) );
			if ( $tool_id ) {
				$tool = AW()->tools_service()->get_tool( $tool_id );
				if ( $tool ) {
					$page_info['title'] = [
						$page_info['title'],
						$tool->title,
					];
					$page_info['path']  = add_query_arg(
						[ 'page' => 'automatewoo-tools' ],
						'admin.php'
					);
				}
			}
			wc_admin_connect_page( $page_info );
		}
	}
}

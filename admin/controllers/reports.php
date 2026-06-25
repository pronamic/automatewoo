<?php

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\HPOS_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Reports
 */
class Reports extends Base {

	/** @var array */
	private $reports = [];


	/**
	 * Handle controller requests.
	 *
	 * @return void
	 */
	public function handle() {

		if ( HPOS_Helper::is_HPOS_enabled() ) {
			wp_safe_redirect( $this->get_corresponding_analytics_url() );
			return;
		}

		$this->handle_actions();
		$this->output_list_table();
	}

	/**
	 * Show deprecation warning above other success and error messages.
	 */
	public function output_messages() {
		$analytics_link = '<a href="' . esc_url( $this->get_corresponding_analytics_url() ) . '">' . esc_html__( 'Analytics', 'automatewoo' ) . '</a>';

		// Show the warning.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- format_notice() returns trusted HTML; main text is escaped internally and the extra text is intentionally trusted markup.
		echo $this->format_notice(
			[
				'main'  => __( 'This reports page is deprecated.', 'automatewoo' ),
				'extra' => sprintf(
					/* translators: %s Analytics link, to migrated reports. */
					__( 'All reports were migrated to %s. This page will be removed once High Performance Order Storage is enabled in WooCommerce.', 'automatewoo' ),
					$analytics_link
				),
				'class' => '',
			],
			'warning'
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		// Show other messages.
		parent::output_messages();
	}


	/**
	 * Output the reports list table view.
	 *
	 * @return void
	 */
	public function output_list_table() {
		$this->output_view(
			'page-reports',
			[
				'current_tab' => $this->get_current_tab(),
				'tabs'        => $this->get_reports_tabs(),
			]
		);
	}


	/**
	 * Handle actions for the current reports tab.
	 *
	 * @return void
	 */
	public function handle_actions() {
		$current_tab = $this->get_current_tab();
		$current_tab->handle_actions( $this->get_current_action() );
	}



	/**
	 * @return \AW_Admin_Reports_Tab_Abstract|false
	 */
	public function get_current_tab() {

		$tabs = $this->get_reports_tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin tab selection; value sanitized, no state change.
		$current_tab_id = empty( $_GET['tab'] ) ? current( $tabs )->id : sanitize_title( wp_unslash( $_GET['tab'] ) );

		return isset( $tabs[ $current_tab_id ] ) ? $tabs[ $current_tab_id ] : false;
	}


	/**
	 * @return array
	 */
	public function get_reports_tabs() {

		if ( empty( $this->reports ) ) {
			$path = AW()->path( '/admin/reports-tabs/' );

			$report_includes = [];

			$report_includes[] = $path . 'runs-by-date.php';
			$report_includes[] = $path . 'email-tracking.php';
			$report_includes[] = $path . 'conversions.php';
			$report_includes[] = $path . 'conversions-list.php';

			$report_includes = apply_filters( 'automatewoo/reports/tabs', $report_includes );

			foreach ( $report_includes as $report_include ) {
				/** @var \AW_Admin_Reports_Tab_Abstract $class */
				$class                       = require_once $report_include;
				$class->controller           = $this;
				$this->reports[ $class->id ] = $class;
			}
		}

		return $this->reports;
	}

	/**
	 * Return an URL for the Analytics page with the same reports.
	 */
	public function get_corresponding_analytics_url() {
		// Point to current tab's equivalent.
		$path = $this->get_current_tab()->id;
		if ( $path === 'conversions-list' ) {
			$path = 'conversions';
		}
		// Construct the AnchorElement.
		return add_query_arg(
			array(
				'page' => 'wc-admin',
				'path' => '/analytics/automatewoo-' . $path,
			),
			admin_url( 'admin.php' )
		);
	}
}

return new Reports();

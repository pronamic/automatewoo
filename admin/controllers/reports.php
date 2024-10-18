<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;
use AutomateWoo\HPOS_Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Reports
 */
class Reports extends Base {

	/** @var array */
	private $reports = [];


	function handle() {

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
	function output_messages() {
		$analytics_link = '<a href="' . esc_url( $this->get_corresponding_analytics_url() ) . '">' . __( 'Analytics', 'automatewoo' ) . '</a>';

		// Show the warning.
		echo $this->format_notice( [
			'main' => __( 'This reports page is deprecated.', 'automatewoo' ),
			'extra' => sprintf( __( 'All reports were migrated to %1s. This page will be removed once High Performance Order Storage is enabled in WooCommerce.', 'automatewoo' ), $analytics_link ),
			'class' => '',
		], 'warning' );

		// Show other messages.
		parent::output_messages();
	}


	function output_list_table() {
		$this->output_view( 'page-reports', [
			'current_tab' => $this->get_current_tab(),
			'tabs' => $this->get_reports_tabs()
		]);
	}


	function handle_actions() {
		$current_tab = $this->get_current_tab();
		$current_tab->handle_actions( $this->get_current_action() );
	}



	/**
	 * @return \AW_Admin_Reports_Tab_Abstract|false
	 */
	function get_current_tab() {

		$tabs = $this->get_reports_tabs();

		$current_tab_id = empty( $_GET['tab'] ) ? current($tabs)->id : sanitize_title( $_GET['tab'] );

		return isset( $tabs[$current_tab_id] ) ? $tabs[$current_tab_id] : false;
	}


	/**
	 * @return array
	 */
	function get_reports_tabs() {

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
				$class = require_once $report_include;
				$class->controller = $this;
				$this->reports[$class->id] = $class;
			}
		}

		return $this->reports;
	}

	/**
	 * Return an URL for the Analytics page with the same reports.
	 */
	function get_corresponding_analytics_url() {
		// Point to current tab's equivalent.
		$path = $this->get_current_tab()->id;
		if ( $path === 'conversions-list' ) {
			$path = 'conversions';
		}
		// Construct the AnchorElement.
		return add_query_arg( array(
			'page' => 'wc-admin',
			'path' => '/analytics/automatewoo-' . $path,
		), admin_url( 'admin.php' ) );
	}

}

return new Reports();

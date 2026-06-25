<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Admin_Report' ) ) {
	require_once WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php';
}

/**
 * @class AW_Report_Abstract_Graph
 */
class AW_Report_Abstract_Graph extends WC_Admin_Report {

	/** @var array */
	public $chart_colours = [];


	/**
	 * Output the report
	 */
	public function output_report() {

		$ranges = [
			'year'       => __( 'Year', 'automatewoo' ),
			'last_month' => __( 'Last Month', 'automatewoo' ),
			'month'      => __( 'This Month', 'automatewoo' ),
			'7day'       => __( 'Last 7 Days', 'automatewoo' ),
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report; value sanitized, no state change.
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day';

		if ( ! in_array( $current_range, [ 'custom', 'year', 'last_month', 'month', '7day' ], true ) ) {
			$current_range = '7day';
		}

		$this->calculate_current_range( $current_range );

		include WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php';
	}



	/**
	 * Output an export link
	 */
	public function get_export_button() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report; value sanitized, no state change.
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day';
		?>
		<a
			href="#"
			download="automatewoo-report-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'automatewoo' ); ?>"
			data-groupby="<?php echo esc_js( $this->chart_groupby ); ?>"
		>
			<?php esc_html_e( 'Export CSV', 'automatewoo' ); ?>
		</a>
		<?php
	}



	/**
	 * @return array
	 */
	public function get_filtered_workflows() {

		$workflow_ids = AutomateWoo\Clean::ids( aw_request( 'workflow_ids' ) );

		if ( is_array( $workflow_ids ) ) {
			return array_filter( array_map( 'absint', $workflow_ids ) );
		} elseif ( $workflow_ids ) {
			return [ absint( $workflow_ids ) ];
		}
	}


	/**
	 * Workflows selection widget
	 */
	public function output_workflows_widget() {
		?>
		<h4 class="section_title"><span><?php esc_html_e( 'Workflow Search', 'automatewoo' ); ?></span></h4>
		<div class="section">
			<form method="GET">
				<div>
					<select class="wc-product-search" style="width:203px;" name="workflow_ids[]" aria-label="<?php esc_attr_e( 'Filter by workflow', 'automatewoo' ); ?>" data-placeholder="<?php esc_attr_e( 'Search for a workflow&hellip;', 'automatewoo' ); ?>" data-action="aw_json_search_workflows"></select>
					<input type="submit" class="submit button" value="<?php esc_attr_e( 'Show', 'automatewoo' ); ?>" />
					<?php AutomateWoo\Admin::get_hidden_form_inputs_from_query( [ 'range', 'start_date', 'end_date', 'page', 'tab' ] ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}

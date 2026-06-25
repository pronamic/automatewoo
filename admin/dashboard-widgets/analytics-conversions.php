<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Analytics_Conversions class.
 *
 * @since 5.7.0
 */
class Dashboard_Widget_Analytics_Conversions extends Dashboard_Widget_Analytics {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'analytics-conversions';

	/**
	 * Report page id to be used for "view report" link.
	 *
	 * @var string
	 */
	protected $report_page_id = 'conversions';

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		if ( ! $this->date_to || ! $this->date_from ) {
			return;
		}
		?>

		<automatewoo-dashboard-chart
				aw-loading
				class="automatewoo-dashboard-chart"
				after="<?php echo esc_js( $this->date_from->format( 'Y-m-d\TH:i:s' ) ); ?>"
				before="<?php echo esc_js( $this->date_to->format( 'Y-m-d\TH:i:s' ) ); ?>"
				fields="net_revenue,orders_count"
				endpoint="/wc-analytics/reports/conversions/stats"
				is-currency="true,false"
				interval="<?php echo esc_js( $this->get_interval() ); ?>">
			<div class="automatewoo-dashboard-chart__header">

				<?php $this->output_live_chart_header_group( 'net_revenue', __( 'conversion revenue', 'automatewoo' ), 'blue' ); ?>

				<?php $this->output_live_chart_header_group( 'orders_count', __( 'conversions', 'automatewoo' ), 'purple' ); ?>

				<?php $this->output_report_arrow_link(); ?>
			</div>

			<div class="automatewoo-dashboard-chart__tooltip"></div>

			<automatewoo-dashboard-chart__flot
				class="automatewoo-dashboard-chart__flot"><span class="aw-loader">&nbsp;</span></automatewoo-dashboard-chart__flot>

		</automatewoo-dashboard-chart>

		<?php
	}
}

return new Dashboard_Widget_Analytics_Conversions();

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Chart_Workflows_Run class.
 */
class Dashboard_Widget_Chart_Workflows_Run extends Dashboard_Widget_Chart {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'chart-workflows-run';

	/**
	 * Report page id to be used for "view report" link.
	 *
	 * @var string
	 */
	protected $report_page_id = 'runs-by-date';

	/**
	 * Load the chart's data.
	 *
	 * @return array
	 */
	protected function load_data() {
		$logs      = $this->controller->get_logs();
		$logs_data = [];

		foreach ( $logs as $log ) {
			$date = $log->get_date();

			if ( $date ) {
				$logs_data[] = (object) [
					'date' => $date->convert_to_site_time()->to_mysql_string(),
				];
			}
		}

		return [ array_values( $this->prepare_chart_data( $logs_data, 'date', false, $this->get_interval(), 'day' ) ) ];
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		if ( ! $this->date_to || ! $this->date_from ) {
			return;
		}

		$logs = $this->controller->get_logs();
		$this->render_js();

		?>

		<div class="automatewoo-dashboard-chart">

			<div class="automatewoo-dashboard-chart__header">

				<div class="automatewoo-dashboard-chart__header-group">
					<div class="automatewoo-dashboard-chart__header-figure"><?php echo count( $logs ); ?></div>
					<div class="automatewoo-dashboard-chart__header-text">
						<span class="automatewoo-dashboard-chart__legend automatewoo-dashboard-chart__legend--blue"></span>
						<?php esc_html_e( 'workflows run', 'automatewoo' ); ?>
					</div>
				</div>

				<?php $this->output_report_arrow_link(); ?>
			</div>

			<div class="automatewoo-dashboard-chart__tooltip"></div>

			<div id="automatewoo-dashboard-<?php echo esc_attr( $this->get_id() ); ?>" class="automatewoo-dashboard-chart__flot"></div>

		</div>

		<?php
	}
}

return new Dashboard_Widget_Chart_Workflows_Run();

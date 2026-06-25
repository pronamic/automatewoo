<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Chart_Email class.
 */
class Dashboard_Widget_Chart_Email extends Dashboard_Widget_Chart {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'chart-email';

	/**
	 * Report page id to be used for "view report" link.
	 *
	 * @var string
	 */
	protected $report_page_id = 'email-tracking';

	/**
	 * Email count.
	 *
	 * @var int
	 */
	public $email_count = 0;

	/**
	 * Open count.
	 *
	 * @var int
	 */
	public $open_count = 0;

	/**
	 * Click count.
	 *
	 * @var int
	 */
	public $click_count = 0;

	/**
	 * Load the chart's data.
	 *
	 * @return array
	 */
	protected function load_data() {
		$logs   = $this->controller->get_logs();
		$emails = [];
		$opens  = [];
		$clicks = [];
		$series = [];

		foreach ( $logs as $log ) {

			$date = $log->get_date();

			if ( ! $log->is_tracking_enabled() || ! $date ) {
				continue;
			}

			++$this->email_count;

			$emails[] = (object) [
				'date' => $date->convert_to_site_time()->to_mysql_string(),
			];

			if ( $log->has_open_recorded() ) {
				++$this->open_count;
				$opens[] = (object) [
					'date' => $log->get_date_opened()->convert_to_site_time()->to_mysql_string(),
				];
			}

			if ( $log->has_click_recorded() ) {
				++$this->click_count;
				$clicks[] = (object) [
					'date' => $log->get_date_clicked()->convert_to_site_time()->to_mysql_string(),
				];
			}
		}

		$series['emails'] = array_values( $this->prepare_chart_data( $emails, 'date', '', $this->get_interval(), 'day' ) );
		$series['opens']  = array_values( $this->prepare_chart_data( $opens, 'date', '', $this->get_interval(), 'day' ) );
		$series['clicks'] = array_values( $this->prepare_chart_data( $clicks, 'date', '', $this->get_interval(), 'day' ) );

		return $series;
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		if ( ! $this->date_to || ! $this->date_from ) {
			return;
		}

		$this->render_js();
		?>

		<div class="automatewoo-dashboard-chart">

			<div class="automatewoo-dashboard-chart__header">

				<?php $this->output_static_chart_header_group( $this->email_count, __( 'messages sent', 'automatewoo' ), 'blue' ); ?>

				<?php $this->output_static_chart_header_group( $this->open_count, __( 'opens', 'automatewoo' ), 'purple' ); ?>

				<?php $this->output_static_chart_header_group( $this->click_count, __( 'clicks', 'automatewoo' ), 'green' ); ?>

				<?php $this->output_report_arrow_link(); ?>
			</div>

			<div class="automatewoo-dashboard-chart__tooltip"></div>

			<div id="automatewoo-dashboard-<?php echo esc_attr( $this->get_id() ); ?>" class="automatewoo-dashboard-chart__flot"></div>

		</div>

		<?php
	}
}

return new Dashboard_Widget_Chart_Email();

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Chart_Conversions class.
 */
class Dashboard_Widget_Chart_Conversions extends Dashboard_Widget_Chart {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'chart-conversions';

	/**
	 * Report page id to be used for "view report" link.
	 *
	 * @var string
	 */
	protected $report_page_id = 'conversions';

	/**
	 * Define whether a chart widget represents monetary data or some other type of metric.
	 *
	 * @var bool
	 */
	public $is_currency = true;

	/**
	 * Number of conversions
	 *
	 * @var int
	 */
	public $conversion_count = 0;

	/**
	 * Total monetary value of conversions
	 *
	 * @var int
	 */
	public $conversion_total = 0;


	/**
	 * Load the chart's data.
	 *
	 * @return array
	 */
	protected function load_data() {
		$conversions       = $this->controller->get_conversions();
		$conversions_clean = [];

		foreach ( $conversions as $order ) {
			$conversions_clean[] = (object) [
				'date'  => $order->get_date_created(),
				'total' => $order->get_total(),
			];

			++$this->conversion_count;
			$this->conversion_total += $order->get_total();
		}

		return [ array_values( $this->prepare_chart_data( $conversions_clean, 'date', 'total', $this->get_interval(), 'day' ) ) ];
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

				<div class="automatewoo-dashboard-chart__header-group">
					<div class="automatewoo-dashboard-chart__header-figure"><?php echo wp_kses_post( wc_price( $this->conversion_total ) ); ?></div>
					<div class="automatewoo-dashboard-chart__header-text">
						<span class="automatewoo-dashboard-chart__legend automatewoo-dashboard-chart__legend--blue"></span>
						<?php esc_html_e( 'conversion revenue', 'automatewoo' ); ?>
					</div>
				</div>

				<div class="automatewoo-dashboard-chart__header-group">
					<div class="automatewoo-dashboard-chart__header-figure"><?php echo esc_html( $this->conversion_count ); ?></div>
					<div class="automatewoo-dashboard-chart__header-text"><?php esc_html_e( 'conversions', 'automatewoo' ); ?></div>
				</div>

				<?php $this->output_report_arrow_link(); ?>
			</div>

			<div class="automatewoo-dashboard-chart__tooltip"></div>

			<div id="automatewoo-dashboard-<?php echo esc_attr( $this->get_id() ); ?>" class="automatewoo-dashboard-chart__flot"></div>
		</div>

		<?php
	}
}

return new Dashboard_Widget_Chart_Conversions();

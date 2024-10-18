<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Runs_By_Date
 *
 * @deprecated since 5.6.3 Migrated to Analytics.
 * @see AutomateWoo\Admin\Analytics
 * @see AutomateWoo\Admin\Analytics\Rest_API\Workflow_Runs
 */
class Report_Runs_By_Date extends \AW_Report_Abstract_Graph {

	/** @var array  */
	public $chart_colours = [
		'runs' => '#3498db'
	];

	public $workflow_ids = [];
	public $workflow_ids_titles = [];

	public $logs;
	public $logs_count = 0;


	function __construct() {
		$this->workflow_ids = $this->get_filtered_workflows();
	}


	function load_chart_data() {

		$logs_query = new Log_Query();

		if ( ! empty( $this->workflow_ids ) ) {
			$logs_query->where_workflow( $this->workflow_ids );
		}

		$start_date = new DateTime();
		$start_date->setTimestamp( $this->start_date );

		$end_date = new DateTime();
		$end_date->setTimestamp( $this->end_date );
		$end_date->modify('+1 days');

		// convert to UTC
		$start_date->convert_to_utc_time();
		$end_date->convert_to_utc_time();

		$logs_query->where_date_between( $start_date, $end_date );

		$this->logs = $logs_query->get_results();
		$this->logs_count = count( $this->logs );

	}


	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	function get_chart_legend() {

		$this->load_chart_data();

		$legend = [];

		$legend[] = [
			'title' => sprintf( __( '%s workflows have run for the selected period', 'automatewoo' ), '<strong>' . $this->logs_count . '</strong>' ),
			'color' => $this->chart_colours['runs'],
			'highlight_series' => 0
		];

		return $legend;
	}



	/**
	 * [get_chart_widgets description]
	 *
	 * @return array
	 */
	function get_chart_widgets() {

		$widgets = [];

		if ( ! empty( $this->workflow_ids ) ) {
			$widgets[] = [
				'title'    => __( 'Showing reports for:', 'automatewoo' ),
				'callback' => [ $this, 'current_filters' ]
			];
		}

		$widgets[] = [
			'title'    => '',
			'callback' => [ $this, 'output_workflows_widget' ]
		];

		return $widgets;
	}


	/**
	 * Show current filters
	 */
	function current_filters() {

		$this->workflow_ids_titles = [];

		foreach ( $this->workflow_ids as $workflow_id ) {

			$workflow = new Workflow( $workflow_id );

			if ( $workflow ) {
				$this->workflow_ids_titles[] = $workflow->title;
			}
			else {
				$this->workflow_ids_titles[] = '#' . $workflow_id;
			}
		}

		echo '<p>' . ' <strong>' . implode( ', ', $this->workflow_ids_titles ) . '</strong></p>';
		echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'workflow_ids' ) ) . '">' . __( 'Reset', 'automatewoo' ) . '</a></p>';
	}



	/**
	 * Get the main chart```
	 *
	 * @return string
	 */
	function get_main_chart() {
		global $wp_locale;

		$logs = $this->logs;

		if ( ! is_array( $logs ) )
			$logs = [];

		// convert all dates to site time
		foreach ( $logs as $log ) {
			$log->_date_site_time = get_date_from_gmt( $log->date );
		}

		// Prepare data for report
		$log  = $this->prepare_chart_data( $logs, '_date_site_time', false, $this->chart_interval, $this->start_date, $this->chart_groupby );

		// Encode in json format
		$chart_data = wp_json_encode(
			[
				'logs' => array_values( $log ),
			]
		);

		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">
			var main_chart;

			jQuery(function($){
				var order_data = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( $chart_data ); ?>' ) );

				var drawGraph = function( highlight ) {

					var series = [
					{
						label: "<?php echo esc_js( __( 'Runs', 'automatewoo' ) ) ?>",
						data: order_data.logs,
						yaxis: 2,
						color: '<?php echo $this->chart_colours['runs']; ?>',
						points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
						lines: { show: true, lineWidth: 4, fill: false },
						shadowSize: 0
					}
				];

				if ( highlight !== 'undefined' && series[ highlight ] ) {
					highlight_series = series[ highlight ];

					highlight_series.color = '#9c5d90';

					if ( highlight_series.bars )
						highlight_series.bars.fillColor = '#9c5d90';

					if ( highlight_series.lines ) {
						highlight_series.lines.lineWidth = 5;
					}
				}

			main_chart = jQuery.plot(
				jQuery('.chart-placeholder.main'),
				series,
				{
					legend: {
						show: false
					},
					grid: {
						color: '#aaa',
						borderColor: 'transparent',
						borderWidth: 0,
						hoverable: true
					},
					xaxes: [ {
						color: '#aaa',
						position: "bottom",
						tickColor: 'transparent',
						mode: "time",
						timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
						monthNames: JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( array_values( $wp_locale->month_abbrev ) ) ); ?>' ) ),
						tickLength: 1,
						minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
						font: {
							color: "#aaa"
						}
					} ],
					yaxes: [
						{
							min: 0,
							minTickSize: 10,
							tickDecimals: 0,
							color: '#fff',
							font: { color: "#fff" }
						},
						{
							//position: "right",
							min: 0,
							tickDecimals: 0,
							alignTicksWithAxis: 0,
							color: '#eee',
							font: { color: "#aaa" }
						}
					],
				}
			);

			jQuery('.chart-placeholder').trigger( 'resize ');
			}

			drawGraph();

			$( document.body )
				.on( 'mouseenter', '.highlight_series', function() {
					drawGraph( $(this).data('series') );
				} )
				.on( 'mouseleave', '.highlight_series', function() {
					drawGraph();
				} )
			;
			});
		</script>
	<?php

	}

}

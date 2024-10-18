<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Email_Tracking
 *
 * @deprecated since 5.6.8 Migrated to Analytics.
 * @see AutomateWoo\Admin\Analytics
 * @see AutomateWoo\Admin\Analytics\Rest_API\Email_Tracking
 */
class Report_Email_Tracking extends \AW_Report_Abstract_Graph {

	/** @var array  */
	public $chart_colours = [
		'runs' => '#b1d4ea',
		'opens' => '#3498db',
		'unique_clicks' => '#5cc488',
		'clicks' => '#f1c40f',
		'unsubscribes' => '#e74c3c'
	];

	public $workflow_ids = [];
	public $workflow_ids_titles = [];

	public $logs;
	public $logs_count = 0;

	public $unique_clicks = [];
	public $unique_clicks_count = 0;

	public $clicks = [];
	public $clicks_count = 0;

	public $opens = [];
	public $opens_count = 0;

	/** @var Customer[]  */
	public $unsubscribes = [];
	public $unsubscribes_count = 0;


	function __construct() {
		$this->workflow_ids = $this->get_filtered_workflows();
	}


	function load_chart_data() {
		// Get logs
		$logs_query = new Log_Query();

		if ( $this->workflow_ids ) {
			$logs_query->where_workflow( $this->workflow_ids );
		}

		// get report start and end date in site time
		$start_date = new DateTime();
		$start_date->setTimestamp( $this->start_date );

		$end_date = new DateTime();
		$end_date->setTimestamp( $this->end_date );
		$end_date->modify('+1 days');

		// convert to UTC for queries
		$start_date->convert_to_utc_time();
		$end_date->convert_to_utc_time();

		$logs_query->where_date_between( $start_date, $end_date );
		$logs_query->where( 'tracking_enabled', true );
		$logs_query->where( 'has_blocked_emails', false );

		$logs = $logs_query->get_results();

		// Get clicks from logs
		if ( $logs ) {

			$this->logs = $logs;
			$this->logs_count = count( $this->logs );

			foreach ( $this->logs as $log ) {

				$click_recorded = false;

				if ( $tracking_data = $log->get_meta( 'tracking_data' ) ) {

					foreach ( $tracking_data as $item ) {

						if ( ! isset( $item['type'] ) )
							continue;

						switch ( $item['type'] ) {
							case 'click':
								if ( ! $click_recorded )
								{
									$this->unique_clicks[] = $item;
									$click_recorded = true;
								}
								$this->clicks[] = $item;
								break;

							case 'open':

								$this->opens[] = $item;

								break;

						}
					}
				}
			}

			$this->clicks_count = count( $this->clicks );
			$this->unique_clicks_count = count( $this->unique_clicks );
			$this->opens_count = count( $this->opens );
		}

		// Get unsubscribes
		$unsubscribes_query = new Customer_Query();

		$unsubscribes_query->where( 'unsubscribed', true );
		$unsubscribes_query->where( 'unsubscribed_date', $start_date, '>' );
		$unsubscribes_query->where( 'unsubscribed_date', $end_date, '<' );

		if ( $unsubscribes = $unsubscribes_query->get_results() ) {
			$this->unsubscribes = $unsubscribes;
			$this->unsubscribes_count = count( $this->unsubscribes );
		}
	}



	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	function get_chart_legend() {

		$this->load_chart_data();

		$legend = [];

		$legend[] = array(
			'title' => sprintf( __( '%s trackable messages sent', 'automatewoo' ), '<strong>' . $this->logs_count . '</strong>' ),
			'color' => $this->chart_colours['runs'],
			'highlight_series' => 1
		);

		$legend[] = array(
			'title' => sprintf( __( '%s unique opens', 'automatewoo' ), '<strong>' . $this->opens_count . '</strong>' ),
			'color' => $this->chart_colours['opens'],
			'placeholder' => __( 'This value represents unique opens.', 'automatewoo' ),
			'highlight_series' => 4
		);

		$legend[] = array(
			'title' => sprintf( __( '%s unique clicks', 'automatewoo' ), '<strong>' . $this->unique_clicks_count . '</strong>' ),
			'color' => $this->chart_colours['unique_clicks'],
			'highlight_series' => 2
		);

		$legend[] = array(
			'title' => sprintf( __( '%s clicks', 'automatewoo' ), '<strong>' . $this->clicks_count . '</strong>' ),
			'color' => $this->chart_colours['clicks'],
			'highlight_series' => 3
		);

		$legend[] = array(
			'title' => sprintf( __( '%s unsubscribes', 'automatewoo' ), '<strong>' . $this->unsubscribes_count . '</strong>' ),
			'color' => $this->chart_colours['unsubscribes'],
			'placeholder' => __( 'Unsubscribes are recorded against each workflow so users can unsubscribe to individual workflows.', 'automatewoo' ),
			'highlight_series' => 0
		);

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
				'callback' => array( $this, 'current_filters' )
			];
		}

		$widgets[] = [
			'title'    => '',
			'callback' => array( $this, 'output_workflows_widget' )
		];

		return $widgets;
	}


	/**
	 * Show current filters
	 */
	function current_filters() {

		$this->workflow_ids_titles = [];

		foreach ( $this->workflow_ids as $workflow_id ) {

			$workflow = Factory::get( $workflow_id );

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
	 * Get the main chart
	 *
	 * @return string
	 */
	function get_main_chart() {

		global $wp_locale;

		$logs = $this->logs;
		$clicks = $this->clicks;
		$unique_clicks = $this->unique_clicks;
		$opens = $this->opens;

		if ( ! is_array( $logs ) )
			$logs = array();

		// convert all dates to site time
		foreach ( $logs as $log ) {
			$log->_date_site_time = get_date_from_gmt( $log->date );
		}


		// convert clicks to objects
		$unique_click_objects = [];

		foreach( $unique_clicks as $unique_click ) {
			$click_object = new \stdClass();
			$click_object->date = get_date_from_gmt( $unique_click['date'] );
			$unique_click_objects[] = $click_object;
		}


		// convert clicks to objects
		$click_objects = [];

		foreach( $clicks as $click ) {
			$click_object = new \stdClass();
			$click_object->date = get_date_from_gmt( $click['date'] );
			$click_objects[] = $click_object;
		}


		// convert opens to objects
		$open_objects = [];

		foreach( $opens as $open ) {
			$open_object = new \stdClass();
			$open_object->date = get_date_from_gmt( $open['date'] );
			$open_objects[] = $open_object;
		}


		$unsubscribes = [];

		foreach( $this->unsubscribes as $customer ) {
			$unsubscribe_date = $customer->get_date_unsubscribed();

			if ( $unsubscribe_date ) {
				$unsubscribe = new \stdClass();
				$unsubscribe->date = $unsubscribe_date->convert_to_site_time()->to_mysql_string();
				$unsubscribes[] = $unsubscribe;
			}
		}



		// Prepare data for report
		$unique_click_objects = $this->prepare_chart_data( $unique_click_objects, 'date', false, $this->chart_interval, $this->start_date, $this->chart_groupby );
		$click_objects = $this->prepare_chart_data( $click_objects, 'date', false, $this->chart_interval, $this->start_date, $this->chart_groupby );
		$open_objects = $this->prepare_chart_data( $open_objects, 'date', false, $this->chart_interval, $this->start_date, $this->chart_groupby );
		$logs = $this->prepare_chart_data( $logs, '_date_site_time', false, $this->chart_interval, $this->start_date, $this->chart_groupby );
		$unsubscribes = $this->prepare_chart_data( $unsubscribes, 'date', false, $this->chart_interval, $this->start_date, $this->chart_groupby );

		// Encode in json format
		$chart_data = wp_json_encode(
			[
				'logs'          => array_values( $logs ),
				'opens'         => array_values( $open_objects ),
				'unique_clicks' => array_values( $unique_click_objects ),
				'clicks'        => array_values( $click_objects ),
				'unsubscribes'  => array_values( $unsubscribes ),
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
							label: "<?php echo esc_js( __( 'Unsubscribes', 'automatewoo' ) ) ?>",
							data: order_data.unsubscribes,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['unsubscribes']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0
						},
						{
							label: "<?php echo esc_js( __( 'Logs', 'automatewoo' ) ) ?>",
							data: order_data.logs,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['runs']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0
						},
						{
							label: "<?php echo esc_js( __( 'Unique Clicks', 'automatewoo' ) ) ?>",
							data: order_data.unique_clicks,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['unique_clicks']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0
						},
						{
							label: "<?php echo esc_js( __( 'Clicks', 'automatewoo' ) ) ?>",
							data: order_data.clicks,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['clicks']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0
						},
						{
							label: "<?php echo esc_js( __( 'Opens', 'automatewoo' ) ) ?>",
							data: order_data.opens,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['opens']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0
						},
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
					]
				}
			);

			jQuery( '.chart-placeholder' ).trigger( 'resize' );
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

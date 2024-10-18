<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Report_Conversions
 *
 * @deprecated since 5.6.9 Migrated to Analytics.
 * @see AutomateWoo\Admin\Analytics
 * @see AutomateWoo\Admin\Analytics\Rest_API\Conversions\Stats
 */
class Report_Conversions extends \AW_Report_Abstract_Graph {

	/** @var array  */
	public $chart_colours = [
		'conversion_value' => '#3498db',
		'conversion_number' => '#DBE1E3'
	];

	public $workflow_ids = [];
	public $workflow_ids_titles = [];

	public $conversion_orders = [];

	public $conversion_total_value = 0;
	public $conversion_total_orders = 0;


	/**
	 * Constructor
	 */
	function __construct() {
		$this->workflow_ids = $this->get_filtered_workflows();
	}


	/**
	 *
	 */
	function load_chart_data() {
		$start_date = new DateTime();
		$start_date->setTimestamp( $this->start_date );

		$end_date = new DateTime();
		$end_date->setTimestamp( $this->end_date );
		$end_date->modify('+1 days');

		$meta_query = [];

		if ( $this->workflow_ids ) {
			$meta_query[] = [
				'key' => '_aw_conversion',
				'value' => $this->workflow_ids,
            ];
		}
		else {
			$meta_query[] = [
				'key' => '_aw_conversion',
				'compare' => 'EXISTS',
            ];
		}

		// Get converted orders
		$orders = new \WP_Query([
			'post_type' => 'shop_order',
			'post_status' => array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => $meta_query,
			'date_query' => [
				[
					'column' => 'post_date',
					'after' => $start_date->to_mysql_string()
                ],
				[
					'column' => 'post_date',
					'before' => $end_date->to_mysql_string()
                ]
            ]
		  ]);

		foreach ( $orders->posts as $order_id ) {
			$order = wc_get_order( $order_id );

			$this->conversion_total_value += $order->get_total();

			$order_obj = new \stdClass();
			$order_obj->date = $order->get_date_created()->date( Format::MYSQL ); // keep site time for reports
			$order_obj->total = $order->get_total();

			$this->conversion_orders[] = $order_obj;
		}

		$this->conversion_total_orders = $orders->post_count;
	}



	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	function get_chart_legend() {

		$this->load_chart_data();

		$legend = [];

		$legend[] = [
			'title' => sprintf( __( '%s converted order value', 'automatewoo' ), '<strong>' . wc_price($this->conversion_total_value) . '</strong>' ),
			'color' => $this->chart_colours['conversion_value'],
			'highlight_series' => 1
		];

		$legend[] = [
			'title' => sprintf( __( '%s converted orders', 'automatewoo' ), '<strong>' . $this->conversion_total_orders . '</strong>' ),
			'color' => $this->chart_colours['conversion_number'],
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

		$this->workflow_ids_titles = array();

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
	 * Get the main chart
	 *
	 * @return string
	 */
	function get_main_chart() {

		global $wp_locale;

		// Prepare data for report
		$conversion_value = $this->prepare_chart_data( $this->conversion_orders, 'date', 'total', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$conversion_number = $this->prepare_chart_data( $this->conversion_orders, 'date', false, $this->chart_interval, $this->start_date, $this->chart_groupby );

		// Encode in json format
		$chart_data = wp_json_encode( [
			'conversion_value' => array_values( $conversion_value ),
			'conversion_number' => array_values( $conversion_number ),
		] );

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
							label: "<?php echo esc_js( __( 'Conversion Number', 'automatewoo' ) ) ?>",
							data: order_data.conversion_number,
							yaxis: 1,
							color: '<?php echo $this->chart_colours['conversion_number']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colours['conversion_number']; ?>', fill: true, show: true, lineWidth: 0, barWidth: 60 * 60 * 24 * 1000, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Conversion Value', 'automatewoo' ) ) ?>",
							data: order_data.conversion_value,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['conversion_value']; ?>',
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
							minTickSize: 1,
							tickDecimals: 0,
							color: '#d4d9dc',
							font: { color: "#aaa" }
						},
						{
							position: "right",
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

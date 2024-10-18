<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Chart class.
 */
abstract class Dashboard_Widget_Chart extends Dashboard_Widget_Analytics {

	/**
	 * Define whether a chart widget represents monetary data or some other type of metric.
	 *
	 * @var bool
	 */
	public $is_currency = false;

	/**
	 * The widget's data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Load the chart's data.
	 *
	 * @return array
	 */
	abstract protected function load_data();


	/**
	 * Get the chart's data. Cached.
	 *
	 * @return array
	 */
	protected function get_data() {
		if ( ! isset( $this->data ) ) {
			$this->data = $this->load_data();
		}
		return $this->data;
	}

	/**
	 * Get chart parameters.
	 *
	 * @return array
	 */
	protected function get_params() {
		$params = [
			'interval'    => $this->get_interval(),
			'is_currency' => $this->is_currency,
		];

		return $params;
	}

	/**
	 * Render chart JS.
	 */
	protected function render_js() {
		?>
		<script type="text/javascript">
			jQuery(function(){
				var data = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $this->get_data() ) ); ?>' ) );
				var params = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $this->get_params() ) ); ?>' ) );
				AW.Dashboard.drawGraph( 'automatewoo-dashboard-<?php echo esc_js( $this->get_id() ); ?>', data, params );
			});
		</script>
		<?php
	}

	/**
	 * Dates should be supplied in the site's timezone.
	 *
	 * @param array  $data     array of your data
	 * @param string $date_key key for the 'date' field. e.g. 'post_date'
	 * @param string $data_key key for the data you are charting
	 * @param int    $interval
	 * @param string $group_by
	 *
	 * @return array
	 */
	protected function prepare_chart_data( $data, $date_key, $data_key, $interval, $group_by ) {
		$prepared_data = [];
		// get start in site's timezone
		$start_date = clone $this->date_from;
		$start_date->convert_to_site_time();

		// Ensure all days (or months) have values first in this range
		for ( $i = 0; $i <= $interval; $i++ ) {
			switch ( $group_by ) {
				case 'day':
					$time = strtotime( gmdate( 'Ymd', strtotime( "+{$i} DAY", $start_date->getTimestamp() ) ) ) . '000';
					break;
				case 'month':
				default:
					$time = strtotime( gmdate( 'Ym', strtotime( "+{$i} MONTH", $start_date->getTimestamp() ) ) . '01' ) . '000';
					break;
			}

			if ( ! isset( $prepared_data[ $time ] ) ) {
				$prepared_data[ $time ] = array( esc_js( $time ), 0 );
			}
		}

		foreach ( $data as $d ) {
			$date = $d->$date_key;

			if ( ! $date ) {
				continue;
			}

			if ( ! is_a( $date, 'DateTime' ) ) {
				$date = new DateTime( $date );
			}

			switch ( $group_by ) {
				case 'day':
					$time = strtotime( $date->format( 'Ymd' ) ) . '000';
					break;
				case 'month':
				default:
					$time = strtotime( $date->format( 'Ym' ) . '01' ) . '000';
					break;
			}

			if ( ! isset( $prepared_data[ $time ] ) ) {
				continue;
			}

			if ( $data_key ) {
				$prepared_data[ $time ][1] += $d->$data_key;
			} else {
				++$prepared_data[ $time ][1];
			}
		}

		return $prepared_data;
	}

	/**
	 * Get the URL for the full report.
	 *
	 * @param string $page_id
	 *
	 * @return string
	 */
	protected function get_report_url( $page_id ) {
		// SEMGREP WARING EXPLANATION
		// This URL is escaped later in consumer call (if not, a warning will be produced by PHPCS).
		// Also, these are just dates and a controlled page_url (loaded by id).
		return add_query_arg(
			[
				'range'      => 'custom',
				'start_date' => $this->date_from->format( 'Y-m-d' ),
				'end_date'   => $this->date_to->format( 'Y-m-d' ),
			],
			Admin::page_url( $page_id )
		);
	}
}

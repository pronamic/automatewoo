<?php

namespace AutomateWoo;

use AutomateWoo\Admin\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Analytics class.
 *
 * @since 5.7.0
 */
abstract class Dashboard_Widget_Analytics extends Dashboard_Widget {

	/**
	 * Report page id to be used for "view report" link -
	 * by `get_report_url` in `output_report_arrow_link`.
	 *
	 * @var string
	 */
	protected $report_page_id;

	/**
	 * Get the current date range interval.
	 *
	 * @return int
	 */
	protected function get_interval() {
		return absint( ceil( max( 0, ( $this->date_to->getTimestamp() - $this->date_from->getTimestamp() ) / ( 60 * 60 * 24 ) ) ) );
	}

	/**
	 * Get the URL for the full report.
	 *
	 * @param string $page_id
	 *
	 * @return string
	 */
	protected function get_report_url( $page_id ) {
		if ( Analytics::is_enabled() ) {
			// SEMGREP WARNING EXPLANATION
			// This is being escaped later in the consumer call (if not, a warning will be produced by PHPCS).
			return add_query_arg(
				[
					'period'  => 'custom',
					'compare' => 'previous_year',
					'after'   => $this->date_from->format( 'Y-m-d\TH:i:s' ),
					'before'  => $this->date_to->format( 'Y-m-d\TH:i:s' ),
				],
				Admin::page_url( 'analytics', $page_id )
			);
		} else {
			// SEMGREP WARNING EXPLANATION
			// This is being escaped later in the consumer call (if not, a warning will be produced by PHPCS).
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

	/**
	 * Output arrow link to the full report.
	 */
	protected function output_report_arrow_link() {
		?>
		<a href="<?php echo esc_url( $this->get_report_url( $this->report_page_id ) ); ?>" class="automatewoo-arrow-link">
			<span class="screen-reader-text"><?php esc_html_e( 'View report', 'automatewoo' ); ?></span>
		</a>
		<?php
	}
}

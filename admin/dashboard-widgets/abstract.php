<?php

namespace AutomateWoo;

/**
 * Dashboard_Widget class.
 *
 * @since 2.8
 */
abstract class Dashboard_Widget {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Widget's title.
	 *
	 * @var string optional
	 */
	public $title = '';

	/**
	 * Set date range for widget.
	 *
	 * @var DateTime
	 */
	public $date_from;

	/**
	 * Set date range for widget.
	 *
	 * @var DateTime
	 */
	public $date_to;

	/**
	 * Show/hide widget.
	 *
	 * @var bool
	 */
	public $display = true;

	/**
	 * Current instance of dashboard controller.
	 *
	 * @var Admin\Controllers\Dashboard
	 */
	public $controller;

	/**
	 * Get widget's ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set GMT date range.
	 *
	 * @param DateTime $from
	 * @param DateTime $to
	 */
	public function set_date_range( $from, $to ) {
		$this->date_from = $from;
		$this->date_to   = $to;
	}

	/**
	 * Display the widget.
	 */
	public function output() {
		ob_start();
		$this->output_before();
		$this->output_content();
		$this->output_after();
		$output = ob_get_clean();

		if ( $this->display ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {}

	/**
	 * Output before the widget content.
	 */
	protected function output_before() {
		$classes = 'automatewoo-dashboard-widget automatewoo-dashboard-widget--' . $this->get_id();
		echo '<div class="' . esc_attr( $classes ) . '">';
		echo '<div class="automatewoo-dashboard-widget__content">';
	}

	/**
	 * Output after the widget content.
	 */
	protected function output_after() {
		echo '</div></div>';
	}
}

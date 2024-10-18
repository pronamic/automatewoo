<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo;
use AutomateWoo\Options;
use AutomateWoo\Time_Helper;
use AutomateWoo\Dashboard_Widget;
use AutomateWoo\Clean;
use AutomateWoo\DateTime;
use AutomateWoo\Admin\Analytics\Rest_API;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Dashboard
 */
class Dashboard extends Base {

	/** @var array */
	private $widgets;

	/** @var array */
	private $logs;

	/** @var array */
	private $carts;

	/** @var array */
	private $guests;

	/** @var array */
	private $optins_count;

	/** @var array */
	private $conversions;

	/** @var int */
	private $guests_count;

	/** @var int */
	private $active_carts_count;

	/** @var int */
	private $queued_count;


	function handle() {

		wp_enqueue_script( 'automatewoo-dashboard' );

		$this->maybe_set_date_cookie();

		$widgets = $this->get_widgets();
		$date_arg = $this->get_date_arg();
		$date_range = $this->get_date_range();
		$date_tabs = [
			'90days' => __( '90 days', 'automatewoo' ),
			'30days' => __( '30 days', 'automatewoo' ),
			'14days' => __( '14 days', 'automatewoo' ),
			'7days' => __( '7 days', 'automatewoo' )
		];

		foreach ( $widgets as $i => $widget ) {
			$widget->set_date_range( $date_range['from'], $date_range['to'] );
			if ( ! $widget->display ) {
				unset( $widgets[$i] );
			}
		}

		$this->output_view( 'page-dashboard', [
			'widgets' => $widgets,
			'date_text' => $date_tabs[$date_arg],
			'date_current' => $this->get_date_arg(),
			'date_tabs' => $date_tabs
		]);
	}


	/**
	 * @return Dashboard_Widget[]
	 */
	function get_widgets() {

		if ( ! isset( $this->widgets ) ) {

			$path = AW()->path( '/admin/dashboard-widgets/' );

			$includes = [];

			if ( Rest_API::is_enabled() ) {
				$includes[] = $path . 'analytics-workflows-run.php';
				$includes[] = $path . 'analytics-conversions.php';
				$includes[] = $path . 'analytics-email.php';
			} else {
				$includes[] = $path . 'chart-workflows-run.php';
				$includes[] = $path . 'chart-conversions.php';
				$includes[] = $path . 'chart-email.php';
			}

			$includes = apply_filters( 'automatewoo/dashboard/chart_widgets', $includes );

			$includes[] = $path . 'key-figures.php';
			$includes[] = $path . 'workflows.php';
			$includes[] = $path . 'logs.php';
			$includes[] = $path . 'queue.php';

			$includes = apply_filters( 'automatewoo/dashboard/widgets', $includes );

			foreach ( $includes as $include ) {
				/** @var Dashboard_Widget $class */
				$class = require_once $include;
				$class->controller = $this;
				$this->widgets[ $class->id ] = $class;
			}
		}

		return $this->widgets;
	}


	/**
	 * @return string
	 */
	function get_date_arg() {

		$cookie_name = 'automatewoo_dashboard_date';

		if ( ! aw_request( 'date' ) && isset( $_COOKIE[ $cookie_name ] ) ) {
			return Clean::string( $_COOKIE[ $cookie_name ] );
		}

		if ( aw_request( 'date' ) ) {
			$date = Clean::string( aw_request( 'date' ) );
			return $date;
		}

		return '30days';
	}


	function maybe_set_date_cookie() {
		if ( aw_request( 'date' ) ) {
			$date = Clean::string( aw_request( 'date' ) );
			if ( ! headers_sent() ) wc_setcookie( 'automatewoo_dashboard_date', $date, time() + MONTH_IN_SECONDS * 2, is_ssl() );
		}
	}


	/**
	 * @return array
	 */
	function get_date_range() {

		$range = $this->get_date_arg();

		$from = new DateTime();
		$to = new DateTime();

		switch ( $range ) {
			case '14days':
				$from->modify( "-14 days" );
				break;
			case '7days':
				$from->modify( "-7 days" );
				break;
			case '30days':
				$from->modify( "-30 days" );
				break;
			case '90days':
				$from->modify( "-90 days" );
				break;
		}

		return apply_filters( 'automatewoo/dashboard/date_range', [
			'from' => $from,
			'to' => $to
		]);
	}


	/**
	 * @return AutomateWoo\Log[]
	 */
	function get_logs() {
		if ( ! isset( $this->logs ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Log_Query();
			$query->where_date_between( $date['from'], $date['to'] );

			$this->logs = $query->get_results();
		}

		return $this->logs;
	}


	/**
	 * @return int
	 */
	function get_active_carts_count() {
		if ( ! isset( $this->active_carts_count ) ) {
			$query = new AutomateWoo\Cart_Query();
			$query->where_status( 'active' );
			$this->active_carts_count = $query->get_count();
		}

		return $this->active_carts_count;
	}


	/**
	 * @return AutomateWoo\Guest[]
	 */
	function get_guests() {
		if ( ! isset( $this->guests ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Guest_Query();
			$query->where( 'created', $date['from'], '>' );
			$query->where( 'created', $date['to'], '<' );

			$this->guests = $query->get_results();
		}

		return $this->guests;
	}


	/**
	 * @return int
	 */
	function get_guests_count() {
		if ( ! isset( $this->guests_count ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Guest_Query();
			$query->where( 'created', $date['from'], '>' );
			$query->where( 'created', $date['to'], '<' );

			$this->guests_count = $query->get_count();
		}

		return $this->guests_count;
	}


	/**
	 * @return int
	 */
	function get_queued_count() {
		if ( ! isset( $this->queued_count ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Queue_Query();
			$query->where_date_created_between( $date['from'], $date['to'] );

			$this->queued_count = $query->get_count();
		}

		return $this->queued_count;
	}


	/**
	 * Get customers who have opted IN or OUT
	 * (whichever is the opposite of the default configured setting).
	 *
	 * @return int
	 */
	public function get_optins_count() {
		if ( ! isset( $this->optins_count ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Customer_Query();

			if ( Options::optin_enabled() ) {
				$query->where( 'subscribed', true );
				$query->where( 'subscribed_date', $date['from'], '>' );
				$query->where( 'subscribed_date', $date['to'], '<' );
			} else {
				$query->where( 'unsubscribed', true );
				$query->where( 'unsubscribed_date', $date['from'], '>' );
				$query->where( 'unsubscribed_date', $date['to'], '<' );
			}


			$this->optins_count = $query->get_count();
		}

		return $this->optins_count;
	}


	/**
	 * @return \WC_Order[]
	 */
	function get_conversions() {
		if ( ! isset( $this->conversions ) ) {

			$date = $this->get_date_range();

			$this->conversions = wc_get_orders(
				[
					'type'         => 'shop_order',
					'status'       => wc_get_is_paid_statuses(),
					'limit'        => -1,
					'meta_key'     => '_aw_conversion',
					'meta_compare' => 'EXISTS',
					'date_created' => $date['from']->getTimestamp() . '...' . $date['to']->getTimestamp(),
				]
			);
		}

		return $this->conversions;
	}

}

return new Dashboard();

<?php

namespace AutomateWoo\Actions\Subscriptions;

use AutomateWoo\DateTime;
use AutomateWoo\Fields;
use AutomateWoo\Time_Helper;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Stagger subscription next payment times across a daily window.
 *
 * @since 6.5.0
 */
class StaggerNextPaymentTime extends AbstractEditItem {

	/**
	 * The new next payment datetime assigned during {@see edit_subscription()}, used by {@see get_note()}.
	 *
	 * @var DateTime|null
	 */
	private $new_next_payment;

	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->title       = __( 'Stagger Next Payment Time', 'automatewoo' );
		$this->description = __( 'Change the subscription next payment time based on its position among other subscriptions renewing on the same day. Use with the Subscription Before Renewal trigger to spread renewal processing across a time window. The workflow should run at least one day before the renewal so payment times are never moved on the renewal day itself.', 'automatewoo' );
	}

	/**
	 * Load the fields required for the action.
	 */
	public function load_fields() {
		$start_time_field = ( new Fields\Time() )
			->set_required()
			->set_name( 'window_start_time' )
			->set_title( __( 'Window start time', 'automatewoo' ) )
			->set_description( __( 'The earliest renewal time to assign, in your site timezone.', 'automatewoo' ) );

		$end_time_field = ( new Fields\Time() )
			->set_required()
			->set_name( 'window_end_time' )
			->set_title( __( 'Window end time', 'automatewoo' ) )
			->set_description( __( 'The latest renewal window boundary, in your site timezone. Assigned times are kept before this time.', 'automatewoo' ) );

		$interval_field = ( new Fields\Positive_Number() )
			->set_required()
			->set_name( 'interval_minutes' )
			->set_title( __( 'Interval in minutes', 'automatewoo' ) )
			->set_description( __( 'The number of minutes between each subscription renewal time. If the window is not wide enough to fit every subscription, times wrap back to the start, which may assign multiple subscriptions to the same minute.', 'automatewoo' ) );

		$this->add_field( $start_time_field );
		$this->add_field( $end_time_field );
		$this->add_field( $interval_field );
	}

	/**
	 * Get the stagger options.
	 *
	 * @return array|false
	 */
	protected function get_object_for_edit() {
		$window_start_seconds = $this->get_time_option_as_seconds( 'window_start_time' );
		$window_end_seconds   = $this->get_time_option_as_seconds( 'window_end_time' );
		$interval_minutes     = absint( $this->get_option( 'interval_minutes' ) );

		if (
			false === $window_start_seconds ||
			false === $window_end_seconds ||
			$window_end_seconds <= $window_start_seconds ||
			$interval_minutes < 1
		) {
			return false;
		}

		return [
			'window_start_seconds' => $window_start_seconds,
			'window_end_seconds'   => $window_end_seconds,
			'interval_seconds'     => $interval_minutes * MINUTE_IN_SECONDS,
		];
	}

	/**
	 * Update the subscription next payment time.
	 *
	 * @param array            $stagger_options Stagger options.
	 * @param \WC_Subscription $subscription    Subscription being updated.
	 *
	 * @return bool
	 */
	protected function edit_subscription( $stagger_options, $subscription ) {
		$next_payment = aw_normalize_date( $subscription->get_date( 'next_payment' ) );

		if ( ! $next_payment ) {
			return false;
		}

		$position = $this->get_subscription_position_on_renewal_day( $subscription, $next_payment );

		if ( $position < 1 ) {
			return false;
		}

		$window_seconds = $stagger_options['window_end_seconds'] - $stagger_options['window_start_seconds'];
		$offset_seconds = ( ( $position - 1 ) * $stagger_options['interval_seconds'] ) % $window_seconds;

		$new_next_payment = $this->get_staggered_datetime( $next_payment, $stagger_options['window_start_seconds'] + $offset_seconds );

		if ( $new_next_payment->to_mysql_string() === $next_payment->to_mysql_string() ) {
			return false;
		}

		// Never move the next payment into the past, which would make the renewal due immediately.
		if ( $new_next_payment->to_mysql_string() <= gmdate( 'Y-m-d H:i:s' ) ) {
			return false;
		}

		$subscription->update_dates( [ 'next_payment' => $new_next_payment->to_mysql_string() ] );

		$this->new_next_payment = $new_next_payment;

		return true;
	}

	/**
	 * Get the note on the subscription to record the next payment date change.
	 *
	 * @param array $stagger_options Stagger options (unused, kept for parent signature compatibility — the actual new date is read from {@see $new_next_payment}).
	 * @return string
	 */
	protected function get_note( $stagger_options ) {
		return sprintf(
			/* translators: %1$s: workflow name, %2$s: new next payment date, %3$s: workflow ID */
			__( '%1$s workflow run: staggered next payment date to %2$s. (Workflow ID: %3$d)', 'automatewoo' ),
			$this->workflow->get_title(),
			$this->format_date_for_note( $this->new_next_payment ? $this->new_next_payment->to_mysql_string() : '' ),
			$this->workflow->get_id()
		);
	}

	/**
	 * Get seconds from the start of the day for a time field option.
	 *
	 * @param string $option_name Option name.
	 *
	 * @return int|false
	 */
	private function get_time_option_as_seconds( string $option_name ) {
		$value = $this->get_option( $option_name );

		if ( ! is_array( $value ) || count( $value ) < 2 ) {
			return false;
		}

		$hours   = absint( $value[0] );
		$minutes = absint( $value[1] );

		if ( $hours > 23 || $minutes > 59 ) {
			return false;
		}

		return Time_Helper::calculate_seconds_from_day_start( "{$hours}:{$minutes}" );
	}

	/**
	 * Get the new next payment datetime in UTC.
	 *
	 * @param DateTime $next_payment          Current next payment datetime in UTC.
	 * @param int      $seconds_from_day_start Seconds from local day start.
	 *
	 * @return DateTime
	 */
	private function get_staggered_datetime( DateTime $next_payment, int $seconds_from_day_start ): DateTime {
		$new_next_payment = clone $next_payment;
		$new_next_payment->convert_to_site_time();
		$new_next_payment->set_time_to_day_start();
		$new_next_payment->modify( "{$seconds_from_day_start} seconds" );
		$new_next_payment->convert_to_utc_time();

		return $new_next_payment;
	}

	/**
	 * Get the subscription's 1-based position among subscriptions renewing on the same local day.
	 *
	 * @param \WC_Subscription $subscription Subscription being updated.
	 * @param DateTime         $next_payment Current next payment datetime in UTC.
	 *
	 * @return int
	 */
	private function get_subscription_position_on_renewal_day( $subscription, DateTime $next_payment ): int {
		$subscription_ids = $this->get_active_subscription_ids_for_renewal_day( $next_payment );
		$position         = array_search( $subscription->get_id(), array_map( 'intval', $subscription_ids ), true );

		return false === $position ? 0 : $position + 1;
	}

	/**
	 * Get active subscriptions renewing on the same local day.
	 *
	 * @param DateTime $next_payment Current next payment datetime in UTC.
	 *
	 * @return int[]
	 */
	private function get_active_subscription_ids_for_renewal_day( DateTime $next_payment ): array {
		$day_start = clone $next_payment;
		$day_start->convert_to_site_time();
		$day_start->set_time_to_day_start();

		$day_end = clone $day_start;
		$day_end->set_time_to_day_end();

		$day_start->convert_to_utc_time();
		$day_end->convert_to_utc_time();

		if ( function_exists( 'wcs_get_orders_with_meta_query' ) ) {
			return wcs_get_orders_with_meta_query(
				[
					'type'          => 'shop_subscription',
					'status'        => [ 'wc-active' ],
					'return'        => 'ids',
					'limit'         => -1,
					'orderby'       => 'ID',
					'order'         => 'ASC',
					'no_found_rows' => true,
					'meta_query'    => [
						[
							'key'     => '_schedule_next_payment',
							'compare' => 'BETWEEN',
							'value'   => [ $day_start->to_mysql_string(), $day_end->to_mysql_string() ],
						],
					],
				]
			);
		}

		$query = new WP_Query(
			[
				'post_type'      => 'shop_subscription',
				'post_status'    => [ 'wc-active' ],
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => '_schedule_next_payment',
						'compare' => '>=',
						'value'   => $day_start->to_mysql_string(),
					],
					[
						'key'     => '_schedule_next_payment',
						'compare' => '<=',
						'value'   => $day_end->to_mysql_string(),
					],
				],
			]
		);

		return $query->posts;
	}
}

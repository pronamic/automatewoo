<?php

namespace AutomateWoo;

use DateInterval;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTime
 *
 * @since 4.3.0
 */
class DateTime extends \DateTime {

	/**
	 * Same as parent but forces UTC timezone if no timezone is supplied instead of using the PHP default.
	 *
	 * @param string              $time
	 * @param DateTimeZone|string $timezone
	 *
	 * @throws \Exception Emits Exception in case of an error.
	 * @throws \DateMalformedStringException When a malformed time string is used (PHP 8.3+).
	 */
	public function __construct( $time = 'now', $timezone = null ) {
		if ( ! $timezone ) {
			$timezone = new DateTimeZone( 'UTC' );
		}

		parent::__construct( $time, $timezone instanceof DateTimeZone ? $timezone : null );
	}


	/**
	 * Convert DateTime from site timezone to UTC.
	 *
	 * Note this doesn't actually set the timezone property, it directly modifies the date.
	 *
	 * @return $this
	 */
	public function convert_to_utc_time() {
		Time_Helper::convert_to_gmt( $this );
		return $this;
	}


	/**
	 * Convert DateTime from UTC to the site timezone.
	 *
	 * Note this doesn't actually set the timezone property, it directly modifies the date.
	 *
	 * @return $this
	 */
	public function convert_to_site_time() {
		Time_Helper::convert_from_gmt( $this );
		return $this;
	}


	/**
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function to_mysql_string() {
		return $this->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Set time to the day end in the current timezone.
	 *
	 * @return $this
	 *
	 * @since 4.6.0
	 */
	public function set_time_to_day_start() {
		$this->setTime( 0, 0, 0 );
		return $this;
	}

	/**
	 * Set time to the day start in the current timezone.
	 *
	 * @return $this
	 *
	 * @since 4.6.0
	 */
	public function set_time_to_day_end() {
		$this->setTime( 23, 59, 59 );
		return $this;
	}

	/**
	 * Return a formatted localised date. Wrapper for date_i18n function.
	 *
	 * @since  4.9.8
	 * @param  string $format Date format.
	 * @return string
	 */
	public function format_i18n( $format = 'Y-m-d' ) {
		return date_i18n( $format, $this->getTimestamp() );
	}

	/**
	 * Naturally add months without skipping into the next month.
	 *
	 * @since 5.1.0
	 * @param integer $months_to_add
	 * @throws \Exception When months isn't a valid number.
	 */
	public function add_natural_months( $months_to_add ) {
		$original_day = $this->format( 'd' );
		$this->add( new DateInterval( 'P' . intval( $months_to_add ) . 'M' ) );
		$new_day = $this->format( 'd' );

		if ( $original_day !== $new_day ) {
			// Check if the day is changed, if so we skipped to another month.
			// Subtract days to go back to the last day of previous month.
			$this->sub( new DateInterval( 'P' . intval( $new_day ) . 'D' ) );
		}
	}
}

<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Format
 * @since 2.9
 */
class Format {

	const MYSQL        = 'Y-m-d H:i:s';
	const API_DATETIME = 'Y-m-d\TH:i:s';

	const WEEKDAYS = [
		1 => 'monday',
		2 => 'tuesday',
		3 => 'wednesday',
		4 => 'thursday',
		5 => 'friday',
		6 => 'saturday',
		7 => 'sunday',
	];

	/**
	 * @param int|string|DateTime|\WC_DateTime $date
	 * @param bool|int $max_diff Set to 0 to disable diff format
	 * @param bool $convert_from_gmt If its gmt convert it to local time
	 * @param bool $shorten_month
	 *
	 * @since 3.8 $shorten_month param added
	 *
	 * @return string|false
	 */
	static function datetime( $date, $max_diff = false, $convert_from_gmt = true, $shorten_month = false ) {
		if ( ! $timestamp = self::mixed_date_to_timestamp( $date ) ) {
			return false; // convert to timestamp ensures WC_DateTime objects are in UTC
		}

		if ( $convert_from_gmt ) {
			$timestamp = strtotime( get_date_from_gmt( date( Format::MYSQL, $timestamp ), Format::MYSQL ) );
		}

		$now = current_time( 'timestamp' );

		if ( $max_diff === false ) $max_diff = DAY_IN_SECONDS; // set default

		$diff = $timestamp - $now;

		if ( abs( $diff ) >= $max_diff ) {
			return $date_to_display = date_i18n(  self::get_date_format( $shorten_month ) . ' ' . wc_time_format(), $timestamp );
		}

		return self::human_time_diff( $timestamp );
	}

	/**
	 * Format a DateTime object to API format.
	 *
	 * @param DateTime $datetime
	 * @return string
	 */
	public static function api_datetime( DateTime $datetime ) {
		return $datetime->format( self::API_DATETIME );
	}

	/**
	 * @param int|string|DateTime|\WC_DateTime $date
	 * @param bool|int $max_diff
	 * @param bool $convert_from_gmt If its gmt convert it to local time
	 * @param bool $shorten_month
	 *
	 * @since 3.8 $shorten_month param added
	 *
	 * @return string|false
	 */
	static function date( $date, $max_diff = false, $convert_from_gmt = true, $shorten_month = false ) {
		if ( ! $timestamp = self::mixed_date_to_timestamp( $date ) ) {
			return false; // convert to timestamp ensures WC_DateTime objects are in UTC
		}

		if ( $convert_from_gmt ) {
			$timestamp = strtotime( get_date_from_gmt( date( Format::MYSQL, $timestamp ), Format::MYSQL ) );
		}

		$now = current_time( 'timestamp' );

		if ( $max_diff === false ) $max_diff = WEEK_IN_SECONDS; // set default

		$diff = $timestamp - $now;

		if ( abs( $diff ) >= $max_diff ) {
			return $date_to_display = date_i18n( self::get_date_format( $shorten_month ), $timestamp );
		}

		return self::human_time_diff( $timestamp );
	}


	/**
	 * @since 3.8
	 * @param bool $shorten_month
	 * @return string
	 */
	static function get_date_format( $shorten_month = false ) {
		$format = wc_date_format();

		if ( $shorten_month ) {
			$format = str_replace( 'F', 'M', $format );
		}

		return $format;
	}


	/**
	 * @param integer $timestamp
	 * @return string
	 */
	private static function human_time_diff( $timestamp ) {
		$now = current_time( 'timestamp' );

		$diff = $timestamp - $now;

		if ( $diff < 55 && $diff > -55 ) {
			$diff_string = sprintf( _n( '%d second', '%d seconds', abs( $diff ), 'automatewoo' ), abs( $diff ) );
		}
		else {
			$diff_string = human_time_diff( $now, $timestamp );
		}

		if ( $diff > 0 ) {
			return sprintf( __( '%s from now', 'automatewoo' ), $diff_string );
		}
		else {
			return sprintf( __( '%s ago', 'automatewoo' ), $diff_string );
		}
	}


	/**
	 * @param int|string|DateTime $date
	 * @return int|bool
	 */
	static function mixed_date_to_timestamp( $date ) {
		if ( ! $date ) {
			return false;
		}

		$timestamp = 0;

		if ( is_numeric( $date ) ) {
			$timestamp = $date;
		}
		else {
			if ( is_a( $date, 'DateTime' ) ) { // maintain support for \DateTime
				$timestamp = $date->getTimestamp();
			}
			elseif ( is_string( $date ) ) {
				$timestamp = strtotime( $date );
			}
		}

		if ( $timestamp < 0 ) {
			return false;
		}

		return $timestamp;
	}


	/**
	 * @param integer $day - 1 (for Monday) through 7 (for Sunday)
	 * @return string|false
	 */
	static function weekday( $day ) {

		global $wp_locale;

		$days = [
			1 => $wp_locale->get_weekday(1),
			2 => $wp_locale->get_weekday(2),
			3 => $wp_locale->get_weekday(3),
			4 => $wp_locale->get_weekday(4),
			5 => $wp_locale->get_weekday(5),
			6 => $wp_locale->get_weekday(6),
			7 => $wp_locale->get_weekday(0),
		];

		if ( ! isset( $days[ $day ] ) ) {
			return false;
		}

		return $days[ $day ];
	}

	/**
	 * Format a weekday to API format.
	 *
	 * @param int $day
	 * @return string
	 */
	public static function api_weekday( int $day ) {
		return self::WEEKDAYS[ $day ] ?? self::WEEKDAYS[7];
	}

	/**
	 * Format a weekday from API format to a numeric representation.
	 *
	 * @param string $day
	 * @return int
	 */
	public static function api_weekday_number( string $day ) {
		$day_number = array_search( $day, self::WEEKDAYS, true );
		return false !== $day_number ? $day_number : 7;
	}

	/**
	 * @param integer $day - 1 (for Monday) through 7 (for Sunday)
	 * @return string|false
	 */
	static function weekday_abbrev( $day ) {

		global $wp_locale;
		if ( $name = self::weekday( $day ) ) {
			return $wp_locale->get_weekday_abbrev( $name );
		}

		return false;
	}


	/**
	 * @param string|array $time
	 * @return string
	 */
	static function time_of_day( $time ) {
		if ( is_array( $time ) ) {
			$parts = array_map( 'absint', $time );
		}
		else {
			$parts = explode( ':', $time );
		}

		if ( count( $parts ) !== 2 ) {
			return '-';
		}

		$date = new DateTime();
		$date->setTime( $parts[0], $parts[1] );
		return $date->format( wc_time_format() );
	}


	/**
	 * Format a price decimal value.
	 *
	 * Does NOT localize the decimal.
	 *
	 * @param float|string $number
	 * @param int          $places
	 * @param bool         $trim_zeros
	 *
	 * @return string
	 */
	public static function decimal( $number, $places = null, $trim_zeros = false ) {
		if ( null === $places ) {
			$places = wc_get_price_decimals();
		}

		if ( null === $number ) {
			$number = (float) 0;
		}

		return wc_format_decimal( $number, $places, $trim_zeros );
	}


	/**
	 * @param Customer $customer
	 * @return string
	 */
	static function customer( $customer ) {

		if ( ! $customer ) {
			return false;
		}

		$name = esc_attr( $customer->get_full_name() );
		$email = esc_attr( $customer->get_email() );

		if ( $customer->is_registered() ) {
			$link = get_edit_user_link( $customer->get_user_id() );
		}
		else {
			$link = Admin::page_url('guest', $customer->get_guest_id() );
		}

		return "<a href='$link'>$name</a>" . ( $customer->is_registered() ? '' : ' ' . __( '[Guest]', 'automatewoo' ) ) . " <a href='mailto:$email'>$email</a>";
	}


	/**
	 * @since 4.0
	 * @param $email
	 * @return string
	 */
	static function email( $email ) {
		$email = esc_attr( $email );

		if ( ! aw_is_email_anonymized( $email ) ) {
			return '<a href="mailto:'.$email.'">'.$email.'</a>';
		}

		return $email;
	}


	/**
	 * @since 4.0
	 * @param $val
	 * @return string
	 */
	static function bool( $val ) {
		return $val ? __('Yes','automatewoo') : __('No','automatewoo');
	}



	/**
	 * @deprecated Use Format::decimal() which will round automatically.
	 *
	 * @param string|float $number
	 * @param $places
	 * @return float
	 */
	static function round( $number, $places = null ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'Format::decimal' );

		return (float) Format::decimal( $number, $places );
	}

	/**
	 * Format a HTML link for an ID number.
	 *
	 * @param string  $url
	 * @param integer $id
	 * @return string Formatted link.
	 */
	public static function html_id_link( string $url, int $id ) {
		return self::html_link( $url, '#' . $id );
	}

	/**
	 * Format a HTML link.
	 *
	 * @param string $url
	 * @param string $text
	 * @return string Formatted link.
	 */
	public static function html_link( string $url, string $text ) {
		return sprintf( '<a href="%s">%s</a>', $url, $text );
	}
}

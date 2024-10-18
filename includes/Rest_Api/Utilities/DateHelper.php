<?php

namespace AutomateWoo\Rest_Api\Utilities;

use AutomateWoo\Log;
use DateTimeInterface;

/**
 * DateHelper Trait
 *
 * @since 6.0.12
 */
trait DateHelper {

	/**
	 * Helper to get date data from a Log object.
	 *
	 * @param Log    $log  The log object.
	 * @param string $type The type of date data to get. Valid options are '' (empty),
	 *                     'opened', or 'clicked'.
	 *
	 * @return string
	 */
	private function get_date_response_from_log( $log, $type = '' ) {
		$type   = empty( $type ) ? $type : "_{$type}";
		$method = "get_date{$type}";

		if ( ! is_callable( [ $log, $method ] ) ) {
			return '';
		}

		$date = $log->$method();

		return ( $date instanceof DateTimeInterface ) ? $this->prepare_date_response( $date ) : '';
	}

	/**
	 * Prepare a date object for a REST Response.
	 *
	 * @param DateTimeInterface $date A date object.
	 *
	 * @return string
	 */
	protected function prepare_date_response( DateTimeInterface $date ) {
		return mysql_to_rfc3339( $date->format( 'Y-m-d H:i:s' ) );
	}
}

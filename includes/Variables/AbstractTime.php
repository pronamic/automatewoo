<?php

namespace AutomateWoo\Variables;

use AutomateWoo\DateTime;
use AutomateWoo\Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractTime
 *
 * @since 5.4.0
 */
abstract class AbstractTime extends Variable {

	/**
	 * Formats a time variable using the wc_time_format()
	 *
	 * @param DateTime $datetime
	 *
	 * @return string
	 */
	protected function format_value_from_utc_tz( DateTime $datetime ): string {
		$datetime->convert_to_site_time();
		return $datetime->format_i18n( wc_time_format() );
	}

	/**
	 * Formats a time variable using the wc_time_format()
	 *
	 * @param DateTime $datetime
	 *
	 * @return string
	 */
	protected function format_value_from_local_tz( DateTime $datetime ): string {
		return $datetime->format_i18n( wc_time_format() );
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\Shop;
use Exception;

/**
 * Class ShopDataItem
 *
 * 'Shop' is a psuedo data type since the shop is always available in every workflow and doesn't
 * need to be stored in the workflow queue or logs.
 *
 * @see     Shop
 * @since   5.1.0
 * @package AutomateWoo
 */
class ShopDataItem {

	/**
	 * Get the shop's current date time in UTC.
	 *
	 * @return DateTime
	 *
	 * @throws Exception In case of error.
	 */
	public function get_current_datetime() {
		$datetime = new DateTime();
		$datetime->setTimestamp( gmdate( 'U' ) );

		return $datetime;
	}
}

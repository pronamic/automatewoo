<?php

namespace AutomateWoo\Rules;

use AutomateWoo\ShopDataItem;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShopCurrentDateTime.
 *
 * @since   5.1.0
 * @package AutomateWoo\Rules
 */
class ShopCurrentDateTime extends Abstract_Date {

	/**
	 * Data item type.
	 *
	 * @var string
	 */
	public $data_item = 'shop';

	/**
	 * Use is not set comparison.
	 *
	 * @var bool
	 */
	public $has_is_set = false;

	/**
	 * Use is not set comparison.
	 *
	 * @var bool
	 */
	public $has_is_not_set = false;

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Shop - Current Date/Time', 'automatewoo' );
	}

	/**
	 * Validates rule.
	 *
	 * @param ShopDataItem $shop
	 * @param string       $compare
	 * @param array|null   $value
	 *
	 * @return bool
	 */
	public function validate( $shop, $compare, $value = null ) {
		try {
			return $this->validate_date( $compare, $value, $shop->get_current_datetime() );
		} catch ( Exception $e ) {
			return false;
		}
	}
}

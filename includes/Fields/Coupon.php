<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable coupon field class.
 *
 * @since 4.6.0
 * @package AutomateWoo\Fields
 */
class Coupon extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'coupon';

	/**
	 * Decides whether to find all coupons or recurring coupons only
	 *
	 * @var bool
	 */
	protected $recurring_only = false;

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		if ( $this->recurring_only ) {
			return 'aw_json_search_coupons_recurring';
		}
		return 'aw_json_search_coupons';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		return wc_format_coupon_code( $value );
	}

	/**
	 * Sets the recurring_only value
	 *
	 * @param bool $recurring
	 *
	 * @return void
	 */
	public function set_recurring_only( bool $recurring ) {
		$this->recurring_only = $recurring;
	}

	/**
	 * Gets the recurring_only value
	 * *
	 *
	 * @return bool
	 */
	public function get_recurring_only() {
		return $this->recurring_only;
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 6.0.25
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		return wc_format_coupon_code( $value );
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\IntegerValidator;

/**
 * Class PluginOptions.
 *
 * The intention is this class will eventually replace the \AutomateWoo\Options class as it is injectable and mockable
 * while \AutomateWoo\Options is not.
 *
 * @since 5.1.0
 */
class OptionsStore extends AbstractOptionsStore {

	use IntegerValidator;

	/**
	 * Get the prefix for options used the wp_options table.
	 *
	 * @return string
	 */
	public function get_prefix(): string {
		return 'automatewoo_';
	}

	/**
	 * Get an array of option defaults.
	 *
	 * @return array
	 */
	public function get_defaults(): array {
		return AW()->options()->defaults;
	}

	/**
	 * Is expired coupon cleaning enabled?
	 *
	 * @return bool
	 *
	 * @throws InvalidArgument If the option value is invalid.
	 */
	public function get_clean_expired_coupons_enabled(): bool {
		return $this->get_bool_option( 'clean_expired_coupons' );
	}

	/**
	 * Is cart tracking enabled?
	 *
	 * @return bool
	 *
	 * @throws InvalidArgument If the option value is invalid.
	 */
	public function get_cart_tracking_enabled(): bool {
		return $this->get_bool_option( 'abandoned_cart_enabled' );
	}

	/**
	 * Is cart tracking enabled?
	 *
	 * @return int
	 *
	 * @throws InvalidArgument If the option value is invalid.
	 */
	public function get_abandoned_cart_timeout(): int {
		$value = (int) $this->get_option( 'abandoned_cart_timeout' );
		$this->validate_positive_integer( $value );

		return $value;
	}

	/**
	 * Is opt-in mode enabled or is site using opt-out mode.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	public function get_optin_enabled() {
		return $this->get_option( 'optin_mode' ) === 'optin';
	}
}

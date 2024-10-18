<?php
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

namespace AutomateWoo;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Set of helper functions for High Performance Order Storage (HPOS).
 *
 * @class HPOS_Helper
 * @since 5.7.0
 */
class HPOS_Helper {
	/**
	 * Check if the HPOS feature is enabled.
	 */
	public static function is_HPOS_enabled() {
		return class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
	}
}

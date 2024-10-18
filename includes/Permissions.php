<?php

namespace AutomateWoo;

/**
 * Class Permissions.
 *
 * @since 4.9.0
 */
final class Permissions {

	/**
	 * Can the current user manage WooCommerce & AutomateWoo.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}
}

<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Clean;
use AutomateWoo\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Membership data type class.
 *
 * @since 2.8.3
 */
class Membership extends AbstractDataType {

	/**
	 * Check if the item is a WC_Memberships_User_Membership
	 *
	 * @param mixed $item
	 *
	 * @return bool
	 */
	public function validate( $item ) {
		return is_a( $item, 'WC_Memberships_User_Membership' );
	}


	/**
	 * @param \WC_Memberships_User_Membership $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * Get the full item from its stored format.
	 *
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 *
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		$id = Clean::id( $compressed_item );

		if ( ! Integrations::is_memberships_enabled() || ! $id ) {
			return false;
		}

		$membership = wc_memberships_get_user_membership( $id );

		if ( ! $membership || $membership->get_status() === 'trash' ) {
			return false;
		}

		return $membership;
	}
}

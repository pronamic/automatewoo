<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Integrations;
use AutomateWoo\Order_Guest;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * User data type class.
 */
class User extends AbstractDataType {

	/**
	 * @param \WP_User|Order_Guest $item
	 * @return bool
	 */
	function validate( $item ) {

		if ( ! is_a( $item, 'WP_User' ) && ! is_a( $item, 'AutomateWoo\Order_Guest' ) ) {
			return false;
		}

		if ( ! $item->user_email ) {
			return false; // social login users may not have an email address defined
		}

		return true;
	}


	/**
	 * @param \WP_User $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->ID;
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {

		// if order based trigger always get the user data from the order
		if ( isset( $compressed_data_layer['order'] ) ) {
			if ( $order = wc_get_order( $compressed_data_layer['order'] ) ) {
				return AW()->order_helper->prepare_user_data_item( $order );
			}
		}

		if ( Integrations::is_subscriptions_active() && isset( $compressed_data_layer['subscription'] ) ) {
			if ( $subscription = wcs_get_subscription( $compressed_data_layer['subscription'] ) ) {
				return $subscription->get_user();
			}
		}

		if ( $compressed_item ) {
			return get_user_by( 'id', absint( $compressed_item ) );
		}

		return false;
	}

}

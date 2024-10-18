<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Customer as CustomerModel;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Customer
 */
class Customer extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	function validate( $item ) {
		if ( ! $item instanceof CustomerModel ) {
			return false;
		}

		if ( ! $item->get_email() ) {
			return false; // social login users may not have an email address defined
		}

		return true;
	}


	/**
	 * @param CustomerModel $item
	 * @return int
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {

		if ( $compressed_item ) {
			return Customer_Factory::get( absint( $compressed_item ) );
		}

		// decompress customer from user, order or subscription data if present, used for triggers that have been converted from 'user' to 'customer' data types

		if ( isset( $compressed_data_layer['order'] ) ) {
			if ( $order = wc_get_order( $compressed_data_layer['order'] ) ) {
				return Customer_Factory::get_by_order( $order );
			}
		}

		if ( Integrations::is_subscriptions_active() && isset( $compressed_data_layer['subscription'] ) ) {
			if ( $subscription = wcs_get_subscription( $compressed_data_layer['subscription'] ) ) {
				return Customer_Factory::get_by_user_id( $subscription->get_user_id() );
			}
		}

		if ( isset( $compressed_data_layer['user'] ) ) {
			return Customer_Factory::get_by_user_id( absint( $compressed_data_layer['user'] ) );
		}

		return false;
	}

	/**
	 * Get singular name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Customer', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Customers', 'automatewoo' );
	}

}

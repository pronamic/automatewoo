<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Data_Layer;

defined( 'ABSPATH' ) || exit;

/**
 * Class SubscriptionManual
 *
 * @since   5.0.0
 * @package AutomateWoo
 */
class SubscriptionManual extends AbstractManual {

	/**
	 * Set data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'subscription', 'customer' ];

	/**
	 * Get primary data type.
	 *
	 * @return string
	 */
	public function get_primary_data_type() {
		return 'subscription';
	}

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->title = __( 'Manual - Subscriptions', 'automatewoo' );
	}

	/**
	 * Get data layer from primary data item.
	 *
	 * @param int $subscription
	 *
	 * @return Data_Layer|bool
	 */
	public function get_data_layer( $subscription ) {
		$subscription = wcs_get_subscription( $subscription );

		if ( ! $subscription ) {
			return false;
		}

		return new Data_Layer(
			[
				'subscription' => $subscription,
				'customer'     => Customer_Factory::get_by_user_id( $subscription->get_user_id() ),
			]
		);
	}
}

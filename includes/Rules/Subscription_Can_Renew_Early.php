<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Subscription Can Renew Early class.
 *
 * @since 4.5.0
 *
 * @class Subscription_Can_Renew_Early
 */
class Subscription_Can_Renew_Early extends Abstract_Bool {

	/**
	 * Data Item.
	 *
	 * @var string $data_item
	 */
	public $data_item = DataTypes::SUBSCRIPTION;

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Subscription - Can Renew Early', 'automatewoo' );
	}

	/**
	 * Validate.
	 *
	 * @param \WC_Subscription $subscription
	 * @param string           $compare
	 * @param string           $value
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {

		if ( ! \WCS_Early_Renewal_Manager::is_early_renewal_enabled() ) {
			return false;
		}

		$can_renew_early = wcs_can_user_renew_early( $subscription, $subscription->get_user_id() );

		return $value === 'yes' ? $can_renew_early : ! $can_renew_early;
	}
}

<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Is_Mailchimp_Subscriber
 */
class Customer_Is_Mailchimp_Subscriber extends Abstract_Select_Single {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title       = __( 'Customer - Is Subscribed To MailChimp List', 'automatewoo' );
		$this->placeholder = __( 'Select a list&hellip;', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function get_select_choices() {
		$mailchimp = Integrations::mailchimp();

		return $mailchimp ? $mailchimp->get_lists() : [];
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		$mailchimp = Integrations::mailchimp();

		if ( ! $mailchimp ) {
			return false;
		}

		return $mailchimp->is_subscribed_to_list( $customer->get_email(), $value );
	}
}

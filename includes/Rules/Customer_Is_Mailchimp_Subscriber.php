<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Is_Mailchimp_Subscriber
 */
class Customer_Is_Mailchimp_Subscriber extends Abstract_Select_Single {

	public $data_item = DataTypes::CUSTOMER;


	function init() {
		$this->title = __( 'Customer - Is Subscribed To MailChimp List', 'automatewoo' );
		$this->placeholder = __( 'Select a list&hellip;', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function get_select_choices() {
		return Integrations::mailchimp()->get_lists();
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return Integrations::mailchimp()->is_subscribed_to_list( $customer->get_email(), $value );
	}

}

<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Coupons_Text_Match
 *
 * @since   4.5.0
 * @package AutomateWoo\Rules
 */
class Subscription_Coupons_Text_Match extends Order_Coupons_Text_Match {

	/**
	 * Data item for the rule.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title         = __( 'Subscription - Coupons - Text Match', 'automatewoo' );
		$this->compare_types = $this->get_multi_string_compare_types();
	}
}

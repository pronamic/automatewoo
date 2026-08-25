<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\StringQuickFilter;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Billing_Phone_Text_Match
 */
class Order_Billing_Phone_Text_Match extends Abstract_String implements QuickFilterable {

	use StringQuickFilter;

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Billing Phone - Text Match', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_string( $order->get_billing_phone(), $compare, $value );
	}

	/**
	 * @param string $compare_type
	 * @param mixed  $value
	 * @return ClauseInterface
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_string_quick_filter_clause( 'billing_phone', $compare_type, $value );
	}
}

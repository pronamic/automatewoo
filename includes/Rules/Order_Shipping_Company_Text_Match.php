<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\StringQuickFilter;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Shipping_Company_Text_Match
 */
class Order_Shipping_Company_Text_Match extends Abstract_String implements QuickFilterable {

	use StringQuickFilter;

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Shipping Company - Text Match', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_string( $order->get_shipping_company(), $compare, $value );
	}

	/**
	 * @param string $compare_type
	 * @param mixed  $value
	 * @return ClauseInterface
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_string_quick_filter_clause( 'shipping_company', $compare_type, $value );
	}
}

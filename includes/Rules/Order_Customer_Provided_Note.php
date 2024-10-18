<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\StringQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Customer_Provided_Note
 */
class Order_Customer_Provided_Note extends Abstract_String implements QuickFilterable {

	use StringQuickFilter;

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Customer Provided Note', 'automatewoo' );
	}


	/**
	 * Validates the rule based on options set by a workflow
	 *
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param string    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_string( $order->get_customer_note(), $compare, $value );
	}

	/**
	 * Get quick filter clause for the rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param string $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_string_quick_filter_clause( 'customer_note', $compare_type, $value );
	}
}

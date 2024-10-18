<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\NumericQuickFilter;
use Exception;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderTotal rules class.
 */
class OrderTotal extends Abstract_Number implements QuickFilterable {

	use NumericQuickFilter;

	public $data_item = 'order';

	public $support_floats = true;


	function init() {
		$this->title = __( 'Order - Total', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_number( $order->get_total(), $compare, $value );
	}

	/**
	 * Get quick filter clause for this rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type The rule's compare type.
	 * @param mixed  $value        The rule's expected value.
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_numeric_quick_filter_clause( 'order_total', $compare_type, $value );
	}

}

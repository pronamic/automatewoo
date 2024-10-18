<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Status
 */
class Order_Status extends Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	public $data_item = 'order';


	function init() {
		parent::init();

		$this->title = __( 'Order - Status', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return wc_get_order_statuses();
	}


	/**
	 * @param \WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_select( 'wc-' . $order->get_status(), $compare, $value );
	}


	/**
	 * Get quick filter clause for this rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param array  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_array_quick_filter_clause( 'status', $compare_type, $value );
	}

}

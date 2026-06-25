<?php

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

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Status', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		return wc_get_order_statuses();
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
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

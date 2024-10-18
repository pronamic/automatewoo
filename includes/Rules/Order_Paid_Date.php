<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\DateQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Order paid date rule.
 *
 * @Class   Order_Paid_Date
 * @package AutomateWoo\Rules
 */
class Order_Paid_Date extends Abstract_Date implements QuickFilterable {

	use DateQuickFilter;

	/**
	 * Data item type.
	 *
	 * @var string
	 */
	public $data_item = 'order';

	/**
	 * Order_Paid_Date constructor.
	 */
	public function __construct() {
		$this->has_is_past_comparision = true;

		parent::__construct();
	}

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Order - Paid Date', 'automatewoo' );
	}

	/**
	 * Validates rule.
	 *
	 * @param \WC_Order  $order   The order.
	 * @param string     $compare What variables we're using to compare.
	 * @param array|null $value   The values we have to compare. Null is only allowed when $compare is is_not_set.
	 *
	 * @return bool
	 */
	public function validate( $order, $compare, $value = null ) {
		return $this->validate_date( $compare, $value, aw_normalize_date( $order->get_date_paid() ) );
	}

	/**
	 * Get quick filter clause.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param mixed  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_date_quick_filter_clause( 'date_paid', $compare_type, $value );
	}
}

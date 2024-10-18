<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NumericClause;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class AW_Rule_Is_Guest_Order
 */
class Order_Is_Guest_Order extends Abstract_Bool implements QuickFilterable {

	/**
	 * @var string $data_item
	 */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Is Placed By Guest', 'automatewoo' );
	}

	/**
	 * Validates the rule based on options set by a workflow
	 * The $data_item passed will already be validated
	 *
	 * @param \WC_Order $order
	 * @param mixed     $compare
	 * @param string    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		$is_guest = $order->get_user_id() === 0;

		switch ( $value ) {
			case 'yes':
				return $is_guest;

			case 'no':
				return ! $is_guest;
		}
	}

	/**
	 * Get quick filter clause for the rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type (Usually empty)
	 * @param string $value        (Usually yes/no)
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		$operator = ( $value === 'yes' ? '=' : '>' );
		return new NumericClause( 'customer_user', $operator, 0 );
	}
}

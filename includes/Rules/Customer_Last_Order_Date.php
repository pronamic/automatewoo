<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Utilities\DateQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Customer last order date rule.
 *
 * @Class Customer_Last_Order_Date
 */
class Customer_Last_Order_Date extends Abstract_Date implements NonPrimaryDataTypeQuickFilterable {

	use DateQuickFilter;

	/**
	 * What date we're using to validate.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * Customer_Last_Order_Date constructor.
	 */
	public function __construct() {
		$this->has_is_past_comparision = true;

		parent::__construct();
	}

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Customer - Last Paid Order Date', 'automatewoo' );
	}

	/**
	 * Validates rule.
	 *
	 * @param \AutomateWoo\Customer $customer The customer.
	 * @param string                $compare  What variables we're using to compare.
	 * @param array|null            $value    The values we have to compare. Null is only allowed when $compare is is_not_set.
	 *
	 * @return bool
	 */
	public function validate( $customer, $compare, $value = null ) {
		return $this->validate_date( $compare, $value, $customer->get_date_last_purchased() );
	}

	/**
	 * Get non-primary quick filter clauses for manual order queries.
	 *
	 * @param string $data_type    The data type being filtered.
	 * @param string $compare_type The rule's compare type.
	 * @param mixed  $value        The rule's expected value.
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error generating the clause.
	 */
	public function get_non_primary_quick_filter_clause( $data_type, $compare_type, $value ) {
		if ( DataTypes::ORDER === $data_type ) {
			if ( in_array( $compare_type, [ 'is_set', 'is_not_set' ], true ) ) {
				return new NoOpClause();
			}

			return $this->generate_date_quick_filter_clause( 'date_created', $compare_type, $value );
		}

		return new NoOpClause();
	}
}

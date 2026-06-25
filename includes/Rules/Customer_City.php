<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Utilities\DataTypeConditions;
use AutomateWoo\Rules\Utilities\StringQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_City
 */
class Customer_City extends Abstract_String implements NonPrimaryDataTypeQuickFilterable {

	use StringQuickFilter;
	use DataTypeConditions;

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - City', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return $this->validate_string( $this->data_layer()->get_customer_city(), $compare, $value );
	}

	/**
	 * Get any non-primary data type quick filter clauses for this rule.
	 *
	 * @since 6.5.0
	 *
	 * @param string $data_type    The data type that is being filtered.
	 * @param string $compare_type The rule's compare type.
	 * @param mixed  $value        The rule's expected value.
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_non_primary_quick_filter_clause( $data_type, $compare_type, $value ) {
		if ( $this->is_data_type_order_or_subscription( $data_type ) ) {
			return $this->generate_string_quick_filter_clause( 'billing_city', $compare_type, $value );
		}

		return new NoOpClause();
	}
}

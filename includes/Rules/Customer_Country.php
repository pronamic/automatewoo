<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use AutomateWoo\Rules\Utilities\DataTypeConditions;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Country
 */
class Customer_Country extends Preloaded_Select_Rule_Abstract implements NonPrimaryDataTypeQuickFilterable {

	use ArrayQuickFilter;
	use DataTypeConditions;


	public $data_item = DataTypes::CUSTOMER;


	function init() {
		parent::init();

		$this->title = __( 'Customer - Country', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return WC()->countries->get_allowed_countries();
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		return $this->validate_select( $this->data_layer()->get_customer_country(), $compare, $value );
	}

	/**
	 * Get any non-primary data type quick filter clauses for this rule.
	 *
	 * @since 5.0.0
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
		// Set clause for order and subscription quick filter queries
		if ( $this->is_data_type_order_or_subscription( $data_type ) ) {
			return $this->generate_array_quick_filter_clause( 'billing_country', $compare_type, $value );
		}

		return new NoOpClause();
	}
}

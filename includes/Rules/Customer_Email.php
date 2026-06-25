<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\RuleQuickFilters\Clauses\NumericClause;
use AutomateWoo\RuleQuickFilters\Clauses\OrClause;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Utilities\DataTypeConditions;
use AutomateWoo\Rules\Utilities\StringQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Email
 */
class Customer_Email extends Abstract_String implements NonPrimaryDataTypeQuickFilterable {

	use StringQuickFilter;
	use DataTypeConditions;

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Email', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return $this->validate_string( $this->data_layer()->get_customer_email(), $compare, $value );
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
		// Get clauses for order and subscription queries
		if ( $this->is_data_type_order_or_subscription( $data_type ) ) {
			$billing_email_clause = $this->generate_string_quick_filter_clause( 'billing_email', $compare_type, $value );

			// For exact match, also search by customer user ID so that orders belonging
			// to a registered customer are found even when the billing email differs from
			// the customer's account email. This aligns the quick filter with the
			// validation logic in Data_Layer::get_customer_email(), which resolves
			// registered customers by their account email rather than billing email.
			if ( 'is' === $compare_type ) {
				$user = get_user_by( 'email', $value );
				if ( $user ) {
					return new OrClause(
						[
							$billing_email_clause,
							new NumericClause( 'customer_user', '=', $user->ID ),
						]
					);
				}
			}

			return $billing_email_clause;
		}

		return new NoOpClause();
	}
}

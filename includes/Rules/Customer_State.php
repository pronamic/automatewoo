<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Utilities\DataTypeConditions;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_State
 */
class Customer_State extends Preloaded_Select_Rule_Abstract implements NonPrimaryDataTypeQuickFilterable {

	use ArrayQuickFilter;
	use DataTypeConditions;

	public $data_item = DataTypes::CUSTOMER;


	function init() {
		parent::init();

		$this->title = __( 'Customer - State', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		$return = [];

		foreach ( WC()->countries->get_states() as $country_code => $states ) {
			foreach ( $states as $state_code => $state_name ) {
				$return[ "$country_code|$state_code" ] = aw_get_country_name( $country_code ) . ' - ' . $state_name;
			}
		}

		return $return;
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		$state = $this->data_layer()->get_customer_state();
		$country = $this->data_layer()->get_customer_country();

		return $this->validate_select( "$country|$state", $compare, $value );
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
	 * @return ClauseInterface|ClauseInterface[]
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_non_primary_quick_filter_clause( $data_type, $compare_type, $value ) {
		// Add state clauses for order and subscription queries
		if ( $this->is_data_type_order_or_subscription( $data_type ) ) {
			$states    = [];
			$countries = [];

			foreach ( (array) $value as $option ) {
				$option      = explode( '|', $option );
				$countries[] = $option[0];
				$states[]    = $option[1];
			}

			return [
				$this->generate_array_quick_filter_clause( 'billing_country', $compare_type, $countries ),
				$this->generate_array_quick_filter_clause( 'billing_state', $compare_type, $states )
			];
		}

		return new NoOpClause();
	}
}

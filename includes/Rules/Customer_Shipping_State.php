<?php

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
 * @class Customer_Shipping_State
 */
class Customer_Shipping_State extends Preloaded_Select_Rule_Abstract implements NonPrimaryDataTypeQuickFilterable {

	use ArrayQuickFilter;
	use DataTypeConditions;

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Customer - Shipping State', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		$return = [];

		foreach ( WC()->countries->get_states() as $country_code => $states ) {
			foreach ( $states as $state_code => $state_name ) {
				$return[ "$country_code|$state_code" ] = aw_get_country_name( $country_code ) . ' - ' . $state_name;
			}
		}

		return $return;
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		$state   = $this->data_layer()->get_customer_shipping_state();
		$country = $this->data_layer()->get_customer_shipping_country();

		return $this->validate_select( "$country|$state", $compare, $value );
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
	 * @return ClauseInterface|ClauseInterface[]
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_non_primary_quick_filter_clause( $data_type, $compare_type, $value ) {
		if ( $this->is_data_type_order_or_subscription( $data_type ) ) {
			$states    = [];
			$countries = [];

			foreach ( (array) $value as $option ) {
				$option      = explode( '|', $option );
				$countries[] = $option[0];
				$states[]    = $option[1];
			}

			return [
				$this->generate_array_quick_filter_clause( 'shipping_country', $compare_type, $countries ),
				$this->generate_array_quick_filter_clause( 'shipping_state', $compare_type, $states ),
			];
		}

		return new NoOpClause();
	}
}

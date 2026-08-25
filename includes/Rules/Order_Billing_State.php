<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Billing_State
 */
class Order_Billing_State extends Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Billing State', 'automatewoo' );
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
	 * @param \WC_Order    $order
	 * @param string       $compare
	 * @param array|string $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$state = $order->get_billing_country() . '|' . $order->get_billing_state();

		return $this->validate_select( $state, $compare, $value );
	}

	/**
	 * @param string $compare_type
	 * @param mixed  $value
	 * @return ClauseInterface|ClauseInterface[]
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		if ( 'is_not' === $compare_type ) {
			return new NoOpClause();
		}

		$states    = [];
		$countries = [];

		foreach ( (array) $value as $option ) {
			$option      = array_pad( explode( '|', $option, 2 ), 2, '' );
			$countries[] = $option[0];
			$states[]    = $option[1];
		}

		// These clauses intentionally return a superset when multiple country/state pairs are selected.
		// Full rule validation rejects combinations that were not selected, such as US|SA for US|CA and AU|SA.
		return [
			$this->generate_array_quick_filter_clause( 'billing_country', $compare_type, $countries ),
			$this->generate_array_quick_filter_clause( 'billing_state', $compare_type, $states ),
		];
	}
}

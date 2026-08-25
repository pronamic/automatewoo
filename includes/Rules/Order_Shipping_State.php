<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Shipping_State
 */
class Order_Shipping_State extends Order_Billing_State {

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Shipping State', 'automatewoo' );
	}

	/**
	 * @param \WC_Order    $order
	 * @param string       $compare
	 * @param array|string $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$state = $order->get_shipping_country() . '|' . $order->get_shipping_state();

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
			$this->generate_array_quick_filter_clause( 'shipping_country', $compare_type, $countries ),
			$this->generate_array_quick_filter_clause( 'shipping_state', $compare_type, $states ),
		];
	}
}

<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Rule_Order_Payment_Gateway
 */
class Rule_Order_Payment_Gateway extends Rules\Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	public $data_item = 'order';


	function init() {
		parent::init();

		$this->title = __( 'Order - Payment Gateway', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		$choices = [];

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway->enabled === 'yes' ) {
				$choices[ $gateway->id ] = $gateway->get_title();
			}
		}

		return $choices;
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 *
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_select( $order->get_payment_method(), $compare, $value );
	}

	/**
	 * Get quick filter clause.
	 *
	 * @since 5.0.0
	 *
	 * @param mixed  $value
	 * @param string $compare_type
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_array_quick_filter_clause( 'payment_method', $compare_type, $value );
	}

}

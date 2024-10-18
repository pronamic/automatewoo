<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderShippingCountry rule class.
 */
class OrderShippingCountry extends Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	public $data_item = 'order';


	function init() {
		parent::init();

		$this->title = __( 'Order - Shipping Country', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return WC()->countries->get_allowed_countries();
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_select( $order->get_shipping_country(), $compare, $value );
	}

	/**
	 * Get quick filter clause for this rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param mixed  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_array_quick_filter_clause( 'shipping_country', $compare_type, $value );
	}

}

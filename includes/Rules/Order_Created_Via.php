<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Integrations;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Created_Via
 */
class Order_Created_Via extends Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	public $data_item = 'order';


	function init() {
		parent::init();

		$this->title = __( 'Order - Created Via', 'automatewoo' );
	}


	/**
	 * Load select choices.
	 *
	 * @return array
	 */
	public function load_select_choices() {
		$options = [
			'checkout'  => __( 'Checkout', 'automatewoo' ),
			'store-api' => __( 'Store API (Checkout Block)', 'automatewoo' ),
			'rest-api'  => __( 'REST API', 'automatewoo' ),
			'admin'     => __( 'Admin', 'automatewoo' ),
		];

		if ( Integrations::is_deposits_active() ) {
			$options['wc_deposits'] = __( 'WooCommerce Deposits', 'automatewoo' );
		}

		return $options;
	}


	/**
	 * @param \WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_select( $order->get_created_via(), $compare, $value );
	}

	/**
	 * Get quick filter clause for this rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param array  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_array_quick_filter_clause( 'created_via', $compare_type, $value );
	}

}

<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\NumericQuickFilter;
use AutomateWoo\Rules\Utilities\StringQuickFilter;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Meta
 */
class Order_Meta extends Abstract_Meta implements QuickFilterable {

	use StringQuickFilter;
	use NumericQuickFilter;

	/** @var string */
	public $data_item = 'order';

	/** @var string the prefix of the property name for Quick Filter clauses */
	public static $property_prefix = 'meta.';

	/**
	 * Init the rule
	 */
	public function init() {
		$this->title = __( 'Order - Custom Field', 'automatewoo' );
	}

	/**
	 * Check if the key is an internal meta key, has a getter method, and the
	 * getter has no required parameters.
	 *
	 * Referring to the implementation of the order method `is_internal_meta_key`
	 * in WooCommerce Core, it's crucial to understand why it might inadvertently
	 * trigger PHP notices due to the call of `wc_doing_it_wrong`, Consequently,
	 * relying solely on that method to check if a meta key is internal can be
	 * problematic.
	 *
	 * Ref: https://github.com/woocommerce/woocommerce/blob/9.8.4/plugins/woocommerce/includes/abstracts/abstract-wc-data.php#L324-L345
	 *
	 * @param \WC_Order $order Order to check.
	 * @param string    $key Key to check.
	 * @return string|bool The getter method name if all conditions are met, otherwise false.
	 */
	protected function get_parameterless_internal_meta_key_getter( $order, $key ) {
		$data_store        = $order->get_data_store();
		$internal_meta_key = ! empty( $key ) && $data_store && in_array( $key, $data_store->get_internal_meta_keys(), true );

		if ( ! $internal_meta_key ) {
			return false;
		}

		$method     = 'get_' . ltrim( $key, '_' );
		$has_getter = is_callable( array( $order, $method ) );

		if ( $has_getter ) {
			$reflection_method     = new \ReflectionMethod( $order, $method );
			$required_params_count = $reflection_method->getNumberOfRequiredParameters();

			if ( $required_params_count === 0 ) {
				return $method;
			}
		}

		return false;
	}

	/**
	 * Validate the rule based on options set by a workflow
	 * The $order passed will already be validated
	 *
	 * @param \WC_Order $order
	 * @param string    $compare_type
	 * @param array     $value_data
	 * @return bool
	 */
	public function validate( $order, $compare_type, $value_data ) {

		$value_data = $this->prepare_value_data( $value_data );

		if ( ! is_array( $value_data ) ) {
			return false;
		}

		// Call getter method if it exists, to avoid warnings from `is_internal_meta_key` when calling for internal meta keys.
		$key        = $value_data['key'];
		$method     = $this->get_parameterless_internal_meta_key_getter( $order, $key );
		$meta_value = $method ? $order->$method() : $order->get_meta( $key );

		return $this->validate_meta( $meta_value, $compare_type, $value_data['value'] );
	}

	/**
	 * Get quick filter clause for the rule.
	 *
	 * @since 5.1.0
	 *
	 * @param string $compare_type textual representation of the comparison operator
	 * @param array  $value array containing the custom meta key and value
	 *
	 * @return ClauseInterface StringClause, NumericClause, or NoOpClause
	 *
	 * @throws InvalidArgumentException When there's an error generating the clause.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {

		$value_data = $this->prepare_value_data( $value );
		if ( ! is_array( $value_data ) ) {
			throw new InvalidArgumentException();
		}

		// Use NumericClause for numeric comparisons (greater/less/multiples) and for is/is not ONLY with numeric values
		if ( $this->is_numeric_meta_field( $compare_type, $value_data['value'] ) ) {
			$meta_clause = $this->generate_numeric_quick_filter_clause( self::$property_prefix . $value_data['key'], $compare_type, $value_data['value'] );
		} else {
			$meta_clause = $this->generate_string_quick_filter_clause( self::$property_prefix . $value_data['key'], $compare_type, $value_data['value'] );
		}

		return $meta_clause;
	}
}

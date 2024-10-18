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

		return $this->validate_meta( $order->get_meta( $value_data['key'] ), $compare_type, $value_data['value'] );
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

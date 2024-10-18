<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Meta
 */
abstract class Abstract_Meta extends Rule {

	/** @var string */
	public $type = 'meta';

	/** @var bool */
	public $has_multiple_value_fields = true;

	/**
	 * Abstract_Meta constructor.
	 */
	public function __construct() {
		$this->compare_types = $this->get_string_compare_types() + $this->get_integer_compare_types();
		parent::__construct();
	}


	/**
	 * Validate a meta value.
	 *
	 * @param mixed  $actual_value
	 * @param string $compare_type
	 * @param mixed  $expected_value
	 * @return bool
	 */
	public function validate_meta( $actual_value, $compare_type, $expected_value ) {

		// Meta compares are a mix of string and number comparisons.
		// Validate as a number for numeric comparisons (greater/less/multiples) and for is/is not ONLY with numeric values
		if ( $this->is_numeric_meta_field( $compare_type, $expected_value ) ) {
			return $this->validate_number( $actual_value, $compare_type, $expected_value );
		} else {
			return $this->validate_string( $actual_value, $compare_type, $expected_value );
		}
	}

	/**
	 * Determine whether the meta field can reasonably be evaluated as a number, specifically for
	 * numeric comparisons (greater/less/multiples) and for numeric is/is not.
	 * This can facilitate better comparisons (for example, "5" = "5.0" in numeric comparisons,
	 * but not in string comparisons).
	 *
	 * @since 5.1.0
	 *
	 * @param string $compare_type
	 * @param mixed  $value
	 *
	 * @return bool True if the meta field is determined to be numeric.
	 */
	protected function is_numeric_meta_field( $compare_type, $value ) {
		$is_numeric_compare_type = ( $this->is_integer_compare_type( $compare_type ) && ! $this->is_is_or_is_not_compare_type( $compare_type ) );
		$is_numeric_is_is_not    = ( is_numeric( $value ) && $this->is_is_or_is_not_compare_type( $compare_type ) );

		return $is_numeric_compare_type || $is_numeric_is_is_not;
	}


	/**
	 * Return an associative array with 'key' and 'value' elements.
	 *
	 * @param mixed $value
	 * @return array|false
	 */
	public function prepare_value_data( $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		return [
			'key'   => trim( $value[0] ),
			'value' => isset( $value[1] ) ? $value[1] : false,
		];
	}
}

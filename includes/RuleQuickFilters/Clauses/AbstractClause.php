<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class Clause
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
abstract class AbstractClause implements ClauseInterface {

	/**
	 * The clause property.
	 *
	 * @var string
	 */
	protected $property;

	/**
	 * The clause operator.
	 *
	 * @var string
	 */
	protected $operator;

	/**
	 * The clause value.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Validates the clause value.
	 *
	 * @param mixed $value
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	abstract protected function validate_value( $value );

	/**
	 * Clause constructor.
	 *
	 * @param string $property The property to filter against.
	 * @param string $operator Should be a valid database WHERE operator.
	 * @param mixed  $value    The quick filter clause value.
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	public function __construct( $property, $operator, $value ) {
		$this->validate_value( $value );

		$this->property = $property;
		$this->operator = $operator;
		$this->value    = $value;
	}

	/**
	 * Get the clause property.
	 *
	 * @return string
	 */
	public function get_property() {
		return $this->property;
	}

	/**
	 * Get the clause operator.
	 *
	 * @return string
	 */
	public function get_operator() {
		return $this->operator;
	}

	/**
	 * Get the clause value.
	 *
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}
}

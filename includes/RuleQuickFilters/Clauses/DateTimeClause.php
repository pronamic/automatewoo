<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use AutomateWoo\DateTime;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTimeClause
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class DateTimeClause extends AbstractClause {

	/**
	 * The clause value.
	 *
	 * @var DateTime[]|DateTime
	 */
	protected $value;

	/**
	 * DateTimeClause constructor.
	 *
	 * @param string              $property The property to filter against.
	 * @param string              $operator Should be a valid database WHERE operator.
	 * @param DateTime|DateTime[] $value    The quick filter clause value.
	 *
	 * @throws InvalidArgumentException When a date value is invalid.
	 */
	public function __construct( $property, $operator, $value ) {
		parent::__construct( $property, $operator, $value );
	}

	/**
	 * Validate the date value.
	 *
	 * @param DateTime|DateTime[] $value
	 *
	 * @throws InvalidArgumentException If $value is invalid.
	 */
	protected function validate_value( $value ) {
		if ( is_array( $value ) ) {
			array_map( [ $this, 'validate_value' ], $value );
		} elseif ( ! $value instanceof DateTime ) {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Get the clause value in a timestamp format.
	 *
	 * @return int|array
	 */
	public function get_value_as_timestamp() {
		if ( is_array( $this->value ) ) {
			return array_map(
				function ( $date ) {
					return $date->getTimestamp();
				},
				$this->value
			);
		} else {
			return $this->value->getTimestamp();
		}
	}

	/**
	 * Get the clause value in a MySQL string format.
	 *
	 * @return string|array
	 */
	public function get_value_as_mysql_string() {
		if ( is_array( $this->value ) ) {
			return array_map(
				function ( $date ) {
					return $date->to_mysql_string();
				},
				$this->value
			);
		} else {
			return $this->value->to_mysql_string();
		}
	}
}

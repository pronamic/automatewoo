<?php

namespace AutomateWoo\Rules\Utilities;

use AutomateWoo\DateTime;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\DateTimeClause;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\RuleQuickFilters\Clauses\SetClause;
use AutomateWoo\Time_Helper;
use Exception;

/**
 * Trait TimestampQuickFilter
 *
 * @since   5.0.0
 * @package AutomateWoo\Rules\Utilities
 */
trait DateQuickFilter {

	/**
	 * Get quick filter clause for a date rule.
	 *
	 * All date values specified in rules are assumed to be in the site's timezone since they are user facing.
	 *
	 * // Todo handle logical empty dates, see \AutomateWoo\Rules\Abstract_Date::validate_logical_empty_date()
	 *
	 * @param string $property     The clause property.
	 * @param string $compare_type Supports date-based compare types.
	 * @param array  $rule_values
	 *
	 * @return ClauseInterface|DateTimeClause|SetClause
	 *
	 * @throws Exception When there's an error getting the clause.
	 */
	protected function generate_date_quick_filter_clause( $property, $compare_type, $rule_values ) {
		switch ( $compare_type ) {
			case 'is_after':
				$value    = ( new DateTime( $rule_values['date'] ) )->set_time_to_day_end()->convert_to_utc_time();
				$operator = '>';
				break;

			case 'is_before':
				$value    = ( new DateTime( $rule_values['date'] ) )->set_time_to_day_start()->convert_to_utc_time();
				$operator = '<';
				break;

			case 'is_between':
				return $this->generate_date_between_quick_filter_clause( $property, $rule_values['from'], $rule_values['to'] );

			case 'is_on':
				return $this->generate_date_between_quick_filter_clause( $property, $rule_values['date'], $rule_values['date'] );

			case 'is_not_on':
				return $this->generate_date_between_quick_filter_clause(
					$property,
					$rule_values['date'],
					$rule_values['date'],
					'NOT BETWEEN'
				);

			case 'is_in_the_last':
			case 'is_not_in_the_last':
				$time_period  = Time_Helper::get_period_in_seconds( $rule_values['timeframe'], $rule_values['measure'] );
				$now          = new DateTime();
				$compare_date = new DateTime();
				$compare_date->setTimestamp( $now->getTimestamp() - $time_period );

				$operator = $compare_type === 'is_in_the_last' ? 'BETWEEN' : 'NOT BETWEEN';
				$value    = [ $compare_date, $now ];
				break;

			case 'is_in_the_next':
			case 'is_not_in_the_next':
				$time_period  = Time_Helper::get_period_in_seconds( $rule_values['timeframe'], $rule_values['measure'] );
				$now          = new DateTime();
				$compare_date = new DateTime();
				$compare_date->setTimestamp( $now->getTimestamp() + $time_period );

				$operator = $compare_type === 'is_in_the_next' ? 'BETWEEN' : 'NOT BETWEEN';
				$value    = [ $now, $compare_date ];
				break;

			case 'is_set':
			case 'is_not_set':
				return new SetClause( $property, $compare_type === 'is_set' ? 'SET' : 'NOT SET' );
			default:
				return new NoOpClause();
		}

		return new DateTimeClause( $property, $operator, $value );
	}

	/**
	 * Get quick filter clause for between 2 dates.
	 *
	 * @param string $property
	 * @param string $from_date Expects a valid date string without a time set.
	 * @param string $to_date   Expects a valid date string without a time set.
	 * @param string $operator
	 *
	 * @return DateTimeClause
	 * @throws Exception When there's an error getting the clause.
	 */
	protected function generate_date_between_quick_filter_clause( $property, $from_date, $to_date, $operator = 'BETWEEN' ) {
		$from = ( new DateTime( $from_date ) )->convert_to_utc_time();
		$to   = ( new DateTime( $to_date ) )->set_time_to_day_end()->convert_to_utc_time();

		return new DateTimeClause( $property, $operator, [ $from, $to ] );
	}
}

<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\DateTime;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\DateTimeClause;
use AutomateWoo\RuleQuickFilters\Clauses\SetClause;
use UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

/**
 * Class SubscriptionHighPerformanceDatastoreType.
 *
 * @since   5.6.4
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
class SubscriptionHighPerformanceDatastoreType extends OrderHighPerformanceDatastoreType {

	/**
	 * Map a quick filter clause to query arg.
	 *
	 * @param ClauseInterface $clause
	 *
	 * @throws UnexpectedValueException When there is an error mapping a query arg.
	 */
	protected function map_clause_to_query_arg( $clause ) {
		$property = $clause->get_property();

		switch ( $property ) {
			case 'requires_manual_renewal':
				$this->add_meta_query_arg( '_' . $property, $clause );
				break;
			case 'status':
				$this->add_status_query_arg( $clause, array_keys( wcs_get_subscription_statuses() ) );
				break;
			case 'end_date':
				$this->add_date_meta_query_arg( '_schedule_end', $clause );
				break;
			case 'next_payment_date':
				$this->add_date_meta_query_arg( '_schedule_next_payment', $clause );
				break;
			case 'trial_end_date':
				$this->add_date_meta_query_arg( '_schedule_trial_end', $clause );
				break;
			default:
				parent::map_clause_to_query_arg( $clause );
		}
	}

	/**
	 * Get the default args to use for the query.
	 *
	 * @param int $number
	 * @param int $offset
	 *
	 * @return array
	 */
	protected function get_default_query_args( $number, $offset = 0 ) {
		return wp_parse_args(
			[
				'type'   => 'shop_subscription',
				'status' => array_keys( wcs_get_subscription_statuses() ),
			],
			parent::get_default_query_args( $number, $offset )
		);
	}

	/**
	 * Add meta query arg for date field.
	 *
	 * Dates will be converted to site time since the date column is also in site time.
	 *
	 * @param string                   $meta_key
	 * @param DateTimeClause|SetClause $clause
	 * @param bool                     $use_timestamps Set true if meta field is store as a timestamp.
	 *                                                 False if field is stored as a mysql string.
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_date_meta_query_arg( $meta_key, $clause, $use_timestamps = false ) {
		$operator = $clause->get_operator();
		$value    = $clause->get_value();

		// Special handling for SetClause
		if ( $clause instanceof SetClause ) {
			$this->query_args['meta_query'][] = $this->get_setclause_meta_query_arg( $meta_key, $operator, [ 0, '' ] );
			return;
		}

		if ( ! $clause instanceof DateTimeClause ) {
			throw new UnexpectedValueException();
		}

		switch ( $operator ) {
			case '>':
			case '<':
				if ( ! $value instanceof DateTime ) {
					throw new UnexpectedValueException();
				}
				break;
			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) ) {
					throw new UnexpectedValueException();
				}
				break;
			default:
				throw new UnexpectedValueException();
		}

		if ( $use_timestamps ) {
			$value = $clause->get_value_as_timestamp();
			$type  = 'NUMERIC';
		} else {
			$value = $clause->get_value_as_mysql_string();
			$type  = 'DATETIME';
		}

		$this->query_args['meta_query'][] = [
			'key'     => $meta_key,
			'compare' => $operator,
			'value'   => $value,
			'type'    => $type,
		];
	}
}

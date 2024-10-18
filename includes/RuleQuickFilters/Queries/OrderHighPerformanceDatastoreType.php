<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\DateTime;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\DateTimeClause;
use AutomateWoo\RuleQuickFilters\Clauses\NumericClause;
use AutomateWoo\RuleQuickFilters\Clauses\SetClause;
use AutomateWoo\RuleQuickFilters\Clauses\StringClause;
use AutomateWoo\Rules\Order_Meta;
use UnexpectedValueException;
use WC_Order_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderHighPerformanceDatastoreType.
 *
 * @since   5.5.23
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
class OrderHighPerformanceDatastoreType implements DatastoreTypeInterface {

	/**
	 * Query arguments
	 *
	 * @var array
	 */
	protected $query_args = [];

	protected const STRING_COMPARISONS = [
		'CONTAINS',
		'NOT_CONTAINS',
		'STARTS_WITH',
		'ENDS_WITH',
		'REGEX',
		'=',
		'!=',
	];

	/**
	 * Get quick filter results count by clauses.
	 *
	 * @param ClauseInterface[] $clauses A group of clauses.
	 *
	 * @return int
	 * @throws UnexpectedValueException When there is an error counting results.
	 */
	public function get_results_count_by_clauses( $clauses ) {
		$this->map_clauses_to_query_args( $clauses, 1 );
		$this->query_args['paginate'] = true;
		$query_results                = $this->do_query();

		return (int) $query_results->total;
	}

	/**
	 * Get quick filter results by clauses.
	 *
	 * @param ClauseInterface[] $clauses A group of clauses.
	 * @param int               $number  The number of results to get.
	 * @param int               $offset  The query offset.
	 *
	 * @return array of IDs
	 * @throws UnexpectedValueException When there is an error getting results.
	 */
	public function get_results_by_clauses( $clauses, $number, $offset = 0 ) {
		$this->map_clauses_to_query_args( $clauses, $number, $offset );
		return $this->do_query();
	}

	/**
	 * Map quick filter clauses to WP_Query args.
	 *
	 * @param ClauseInterface[] $clauses
	 * @param int               $number
	 * @param int               $offset
	 *
	 * @throws UnexpectedValueException When there is an error mapping a query arg.
	 */
	protected function map_clauses_to_query_args( $clauses, $number, $offset = 0 ) {
		$this->query_args = $this->get_default_query_args( $number, $offset );

		foreach ( $clauses as $clause ) {
			$this->map_clause_to_query_arg( $clause );
		}
	}

	/**
	 * Map a quick filter clause to query arg.
	 *
	 * @param ClauseInterface $clause
	 *
	 * @throws UnexpectedValueException When there is an error mapping a query arg.
	 */
	protected function map_clause_to_query_arg( $clause ) {
		$property = $clause->get_property();

		// Address custom fields (flagged using the $property_prefix)
		if ( strpos( $property, Order_Meta::$property_prefix ) === 0 ) {
			$property = str_replace( Order_Meta::$property_prefix, '', $property );
			if ( $clause instanceof NumericClause ) {
				$this->add_decimal_meta_query_arg( $property, $clause );
			} else {
				$this->add_meta_query_arg( $property, $clause );
			}
			return;
		}

		switch ( $property ) {
			case 'billing_email':
			case 'billing_country':
			case 'billing_phone':
			case 'billing_postcode':
			case 'billing_state':
			case 'billing_city':
			case 'created_via':
			case 'payment_method':
			case 'shipping_country':
				$this->add_field_query_arg( $property, $clause );
				break;
			case 'customer_note':
				$this->add_string_field_query_arg( $property, $clause );
				break;
			case 'order_total':
				$this->add_decimal_field_query_arg( 'total', $clause );
				break;
			case 'customer_user':
				$this->add_integer_field_query_arg( 'customer_id', $clause );
				break;
			case 'status':
				$this->add_status_query_arg( $clause, array_keys( wc_get_order_statuses() ) );
				break;
			case 'date_paid':
				if ( $clause instanceof SetClause ) {
					$this->add_set_date_query_arg( $property, $clause );
				} else {
					$this->add_date_query_arg( $property, $clause );
				}
				break;
			case 'date_created':
				$this->add_date_query_arg( $property, $clause );
				break;
			default:
				throw new UnexpectedValueException( 'Clause is not mapped.' );
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
		return [
			'type'   => 'shop_order',
			'return' => 'ids',
			'limit'  => $number,
			'offset' => $offset,
			'status' => array_keys( wc_get_order_statuses() ),
		];
	}

	/**
	 * Do WC_Order_Query.
	 *
	 * @return mixed
	 */
	protected function do_query() {
		$query = new WC_Order_Query( $this->query_args );
		return $query->get_orders();
	}

	/**
	 * Add field query arg.
	 *
	 * Can be used for string or array queries.
	 *
	 * @param string          $field
	 * @param ClauseInterface $clause
	 */
	protected function add_field_query_arg( $field, $clause ) {
		$value    = $clause->get_value();
		$operator = $clause->get_operator();

		switch ( $operator ) {
			case 'CONTAINS':
				$operator = 'LIKE';
				break;
			case 'NOT_CONTAINS':
				$operator = 'NOT LIKE';
				break;
			case 'STARTS_WITH':
				$operator = 'REGEXP';
				$value    = '^' . preg_quote( $value, '\'' );
				break;
			case 'ENDS_WITH':
				$operator = 'REGEXP';
				$value    = preg_quote( $value, '\'' ) . '$';
				break;
			case 'REGEX':
				$operator = 'REGEXP';
				$value    = preg_replace( '#^/(.+)/[gi]*$#', '$1', $value );
				break;
			default:
				break;
		}

		$this->query_args['field_query'][] = [
			'field'   => $field,
			'compare' => $operator,
			'value'   => $value,
		];
	}

	/**
	 * Add string field query arg.
	 *
	 * @param string       $field
	 * @param StringClause $clause
	 *
	 * @throws UnexpectedValueException When not using a string comparison.
	 */
	protected function add_string_field_query_arg( $field, StringClause $clause ) {
		if ( ! in_array( $clause->get_operator(), self::STRING_COMPARISONS, true ) ) {
			throw new UnexpectedValueException();
		}

		$this->add_field_query_arg( $field, $clause );
	}

	/**
	 * Add decimal field query arg.
	 *
	 * @param string          $field
	 * @param ClauseInterface $clause
	 */
	protected function add_decimal_field_query_arg( $field, $clause ) {
		$this->query_args['field_query'][] = [
			'field'   => $field,
			'compare' => $clause->get_operator(),
			'value'   => $clause->get_value(),
			'type'    => 'DECIMAL(24,8)',
		];
	}

	/**
	 * Add integer field query arg.
	 *
	 * @param string          $field
	 * @param ClauseInterface $clause
	 */
	protected function add_integer_field_query_arg( $field, $clause ) {
		$this->query_args['field_query'][] = [
			'field'   => $field,
			'compare' => $clause->get_operator(),
			'value'   => $clause->get_value(),
			'type'    => 'NUMERIC',
		];
	}

	/**
	 * Add status query arg.
	 *
	 * @param ClauseInterface $clause
	 * @param array           $valid_statuses Array of all valid statuses.
	 *
	 * @throws UnexpectedValueException When not using a string comparison.
	 */
	protected function add_status_query_arg( $clause, $valid_statuses ) {
		switch ( $clause->get_operator() ) {
			case 'IN':
				$value = $clause->get_value();
				break;
			case 'NOT IN':
				// WP_Order_Query doesn't support querying by the statuses it is not.
				$value = array_diff( $valid_statuses, $clause->get_value() );
				break;
			default:
				throw new UnexpectedValueException();
		}

		$this->query_args['status'] = $value;
	}

	/**
	 * Add basic meta query arg.
	 *
	 * Can be used for string or array queries.
	 *
	 * @param string          $meta_key
	 * @param ClauseInterface $clause
	 */
	protected function add_meta_query_arg( $meta_key, $clause ) {
		$value    = $clause->get_value();
		$operator = $clause->get_operator();

		// Special handling for SetClause
		if ( $clause instanceof SetClause ) {
			$this->query_args['meta_query'][] = $this->get_setclause_meta_query_arg( $meta_key, $operator );
			return;
		}

		switch ( $operator ) {
			case 'CONTAINS':
				$operator = 'LIKE';
				break;
			case 'NOT_CONTAINS':
				$operator = 'NOT LIKE';
				break;
			case 'STARTS_WITH':
				$operator = 'REGEXP';
				$value    = '^' . preg_quote( $value, '\'' );
				break;
			case 'ENDS_WITH':
				$operator = 'REGEXP';
				$value    = preg_quote( $value, '\'' ) . '$';
				break;
			case 'REGEX':
				$operator = 'REGEXP';
				$value    = preg_replace( '#^/(.+)/[gi]*$#', '$1', $value );
				break;
			default:
				break;
		}

		$generated_meta_query = [
			'key'     => $meta_key,
			'compare' => $operator,
			'value'   => $value,
		];

		// For is blank, add NOT EXISTS meta clause in case the meta field doesn't exist at all
		if ( $operator === '=' && $value === '' ) {
			$this->query_args['meta_query'][] = [
				'relation' => 'OR',
				$generated_meta_query,
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
			];
		} else {
			$this->query_args['meta_query'][] = $generated_meta_query;
		}
	}

	/**
	 * Add the NOT EXISTS condition to SET/NOT SET meta queries in order to include cases
	 * where the meta record doesn't exist at all
	 *
	 * @param string       $meta_key           the meta key for the condition
	 * @param string       $operator           SET or NOT SET
	 * @param string|array $empty_values values to consider 'empty', for example [ '', 0 ]
	 *
	 * @return array
	 */
	protected function get_setclause_meta_query_arg( $meta_key, $operator, $empty_values = '' ) {

		// Allow one or multiple empty values
		if ( is_array( $empty_values ) ) {
			$compare = $operator === 'SET' ? 'NOT IN' : 'IN';
		} else {
			$compare = $operator === 'SET' ? '!=' : '=';
		}

		$first_meta_query = [
			'key'     => $meta_key,
			'compare' => $compare,
			'value'   => $empty_values,
			'type'    => 'CHAR',
		];

		// No need to check for non-existent records for "SET"
		if ( 'SET' === $operator ) {
			return $first_meta_query;
		}

		return [
			'relation' => 'OR',
			$first_meta_query,
			[
				'key'     => $meta_key,
				'compare' => 'NOT EXISTS',
			],
		];
	}

	/**
	 * Add query arg for date field.
	 *
	 * Dates will be converted to site time since the date column is also in site time.
	 *
	 * @param string         $field
	 * @param DateTimeClause $clause
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_date_query_arg( $field, $clause ) {
		if ( ! $clause instanceof DateTimeClause ) {
			throw new UnexpectedValueException();
		}

		$operator = $clause->get_operator();
		$value    = $clause->get_value();

		switch ( $operator ) {
			case '>':
			case '<':
				if ( ! $value instanceof DateTime ) {
					throw new UnexpectedValueException();
				}
				$key = $operator === '>' ? 'after' : 'before';

				$this->query_args['date_query'][] = [
					'column'    => "{$field}_gmt",
					'inclusive' => true,
					$key        => $value->convert_to_site_time()->to_mysql_string(),
				];
				break;
			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) ) {
					throw new UnexpectedValueException();
				}

				if ( $operator === 'BETWEEN' ) {
					$key0     = 'after';
					$key1     = 'before';
					$relation = 'AND';
				} else {
					$key0     = 'before';
					$key1     = 'after';
					$relation = 'OR';
				}

				$this->query_args['date_query'][] = [
					'relation' => $relation,
					[
						'column'    => "{$field}_gmt",
						'inclusive' => true,
						$key0       => $value[0]->convert_to_site_time()->to_mysql_string(),
					],
					[
						'column'    => "{$field}_gmt",
						'inclusive' => true,
						$key1       => $value[1]->convert_to_site_time()->to_mysql_string(),
					],
				];
				break;
			default:
				throw new UnexpectedValueException();
		}
	}

	/**
	 * Add set/not set query arg for date field.
	 *
	 * @param string    $field
	 * @param SetClause $clause
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_set_date_query_arg( $field, $clause ) {

		if ( version_compare( WC()->version, 8.1, '>=' ) ) {
			// compatibility-code "WC >= 8.1"
			// Supporting HPOS where NULL is defined for NOT SET fields.
			// @see https://github.com/woocommerce/automatewoo/issues/1571
			$this->query_args['field_query'][] = [
				'field'   => $field,
				'compare' => $clause->get_operator() === 'SET' ? 'EXISTS' : 'NOT EXISTS',
			];
		} else {
			// compatibility-code "WC < 8.1"
			// Supporting HPOS where default values are being used.
			// @see https://github.com/woocommerce/automatewoo/issues/1571
			$this->query_args['field_query'][] = [
				'field'   => $field,
				'compare' => $clause->get_operator() === 'SET' ? '!=' : '=',
				'value'   => '0000-00-00 00:00:00',
			];
		}
	}

	/**
	 * Add decimal meta query arg.
	 *
	 * @param string          $meta_key
	 * @param ClauseInterface $clause
	 */
	protected function add_decimal_meta_query_arg( $meta_key, $clause ) {
		$this->query_args['meta_query'][] = [
			'key'     => $meta_key,
			'compare' => $clause->get_operator(),
			'value'   => $clause->get_value(),
			'type'    => 'DECIMAL(24,8)',
		];
	}
}

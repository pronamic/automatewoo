<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\DateTime;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\DateTimeClause;
use AutomateWoo\RuleQuickFilters\Clauses\SetClause;
use AutomateWoo\RuleQuickFilters\Clauses\StringClause;
use UnexpectedValueException;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractPostDatastoreType for custom post types.
 *
 * @since   5.5.23
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
abstract class AbstractPostDatastoreType implements DatastoreTypeInterface {

	/**
	 * Contains custom where clauses to be added to the WP_Query.
	 *
	 * @var array
	 */
	protected $wp_query_custom_where_clauses = [];

	/**
	 * Get the WP post type for the data type.
	 *
	 * @return string
	 */
	abstract protected function get_post_type();

	/**
	 * Get quick filter results count by clauses.
	 *
	 * @param ClauseInterface[] $clauses A group of clauses.
	 *
	 * @return int
	 * @throws UnexpectedValueException When there is an error counting results.
	 */
	public function get_results_count_by_clauses( $clauses ) {
		$query_args                  = $this->map_clauses_to_wp_query_args( $clauses, 1 );
		$query_args['no_found_rows'] = false;
		$query                       = $this->do_wp_query( $query_args );

		return (int) $query->found_posts;
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
		$query_args = $this->map_clauses_to_wp_query_args( $clauses, $number, $offset );
		$query      = $this->do_wp_query( $query_args );

		return $query->posts;
	}

	/**
	 * Do WP_Query.
	 *
	 * @param array $query_args
	 *
	 * @return WP_Query
	 */
	protected function do_wp_query( $query_args ) {
		add_filter( 'posts_where', [ $this, 'filter_posts_where' ] );
		$query = new WP_Query( $query_args );
		remove_filter( 'posts_where', [ $this, 'filter_posts_where' ] );
		return $query;
	}

	/**
	 * Filters WP_Query posts_where adding extra where clauses.
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function filter_posts_where( $where ) {
		$where .= implode( ' ', $this->wp_query_custom_where_clauses );
		return $where;
	}

	/**
	 * Get the default args to use with WP_Query.
	 *
	 * @param int $number
	 * @param int $offset
	 *
	 * @return array
	 */
	protected function get_default_wp_query_args( $number, $offset = 0 ) {
		return [
			'post_type'      => $this->get_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => $number,
			'offset'         => $offset,
			'no_found_rows'  => true,
			'meta_query'     => [],
		];
	}

	/**
	 * Map quick filter clauses to WP_Query args.
	 *
	 * @param ClauseInterface[] $clauses
	 * @param int               $number
	 * @param int               $offset
	 *
	 * @return array
	 * @throws UnexpectedValueException When there is an error mapping a query arg.
	 */
	protected function map_clauses_to_wp_query_args( $clauses, $number, $offset = 0 ) {
		$query_args = $this->get_default_wp_query_args( $number, $offset );

		foreach ( $clauses as $clause ) {
			$this->map_clause_to_wp_query_arg( $clause, $query_args );
		}

		return $query_args;
	}

	/**
	 * Map a quick filter clause to WP_Query arg.
	 *
	 * @param ClauseInterface $clause
	 * @param array           $query_args Array of WP_Query args.
	 *
	 * @throws UnexpectedValueException When there is an error mapping a query arg.
	 */
	protected function map_clause_to_wp_query_arg( $clause, &$query_args ) {
		throw new UnexpectedValueException( 'Clause is not mapped for WP_Query()' );
	}

	/**
	 * Add basic post meta query arg.
	 *
	 * Can be used for string or array queries.
	 *
	 * @param array           $args
	 * @param string          $meta_key
	 * @param ClauseInterface $clause
	 */
	protected function add_basic_post_meta_query_arg( &$args, $meta_key, $clause ) {
		$value    = $clause->get_value();
		$operator = $clause->get_operator();

		// Special handling for SetClause
		if ( $clause instanceof SetClause ) {
			$args['meta_query'][] = $this->get_setclause_post_meta_query_arg( $meta_key, $operator );
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
			$args['meta_query'][] = [
				'relation' => 'OR',
				$generated_meta_query,
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
			];
		} else {
			$args['meta_query'][] = $generated_meta_query;
		}
	}

	/**
	 * Add post meta query arg for datetime field.
	 *
	 * @param array                    $args
	 * @param string                   $meta_key
	 * @param DateTimeClause|SetClause $clause
	 * @param bool                     $use_timestamps Set true if meta field is store as a timestamp.
	 *                                                 False if field is stored as a mysql string.
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_datetime_post_meta_query_arg( &$args, $meta_key, $clause, $use_timestamps = false ) {
		if ( $clause instanceof SetClause ) {
			$args['meta_query'][] = $this->get_setclause_post_meta_query_arg( $meta_key, $clause->get_operator(), [ 0, '' ] );
		} elseif ( $clause instanceof DateTimeClause ) {
			$operator = $clause->get_operator();
			$value    = $clause->get_value();

			// WP Meta query expects different values depending on the operator
			// This switch will throw an exception to match the behavior of the add_post_date_query_arg method
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
			$args['meta_query'][] = [
				'key'     => $meta_key,
				'compare' => $operator,
				'value'   => $value,
				'type'    => $type,
			];
		} else {
			throw new UnexpectedValueException();
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
	protected function get_setclause_post_meta_query_arg( $meta_key, $operator, $empty_values = '' ) {

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
	 * Add WP query arg for post_date field.
	 *
	 * Dates will be converted to site time since the post_date column is also in site time.
	 *
	 * @param array          $args
	 * @param DateTimeClause $clause
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_post_date_query_arg( &$args, $clause ) {
		if ( ! $clause instanceof DateTimeClause ) {
			throw new UnexpectedValueException();
		}

		global $wpdb;
		$date_query = [];
		$operator   = $clause->get_operator();
		$value      = $clause->get_value();

		switch ( $operator ) {
			case '>':
			case '<':
				if ( ! $value instanceof DateTime ) {
					throw new UnexpectedValueException();
				}
				$key                = $operator === '>' ? 'after' : 'before';
				$date_query[ $key ] = $value->convert_to_site_time()->to_mysql_string();
				break;
			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) ) {
					throw new UnexpectedValueException();
				}

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$this->wp_query_custom_where_clauses[] = $wpdb->prepare(
					" AND ({$wpdb->posts}.post_date {$operator} %s AND %s) ",
					$value[0]->convert_to_site_time()->to_mysql_string(),
					$value[1]->convert_to_site_time()->to_mysql_string()
				);
				// phpcs:enable
				break;
			default:
				// Note: SET/NOT SET clauses are not currently handled for post_date.
				throw new UnexpectedValueException();
		}

		$args['date_query'] = $date_query;
	}

	/**
	 * Add WP query condition for the string post data fields.
	 *
	 * @param string       $column the name of the field in `wp_post` to search
	 * @param StringClause $clause
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_post_column_string_query_arg( $column, StringClause $clause ) {
		global $wpdb;
		$operator = $clause->get_operator();
		$value    = $clause->get_value();

		// Validate the columns to avoid injection
		$allowed_post_columns = [ 'post_excerpt' ];
		if ( ! in_array( $column, $allowed_post_columns, true ) ) {
			throw new UnexpectedValueException();
		}

		switch ( $operator ) {
			case 'CONTAINS':
				$operator = 'LIKE';
				$value    = '%' . $wpdb->esc_like( $value ) . '%';
				break;
			case 'NOT_CONTAINS':
				$operator = 'NOT LIKE';
				$value    = '%' . $wpdb->esc_like( $value ) . '%';
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
			case '=':
			case '!=':
				break;
			default:
				throw new UnexpectedValueException();
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wp_query_custom_where_clauses[] = $wpdb->prepare(
			" AND ({$wpdb->posts}.{$column} {$operator} %s) ",
			$value
		);
		// phpcs:enable
	}

	/**
	 * Add integer post meta query arg.
	 *
	 * @param array           $query_args
	 * @param string          $meta_key
	 * @param ClauseInterface $clause
	 */
	protected function add_integer_post_meta_query_arg( &$query_args, $meta_key, $clause ) {
		$query_args['meta_query'][] = [
			'key'     => $meta_key,
			'compare' => $clause->get_operator(),
			'value'   => $clause->get_value(),
			'type'    => 'NUMERIC',
		];
	}

	/**
	 * Add decimal post meta query arg.
	 *
	 * @param array           $query_args
	 * @param string          $meta_key
	 * @param ClauseInterface $clause
	 */
	protected function add_decimal_post_meta_query_arg( &$query_args, $meta_key, $clause ) {
		$query_args['meta_query'][] = [
			'key'     => $meta_key,
			'compare' => $clause->get_operator(),
			'value'   => $clause->get_value(),
			'type'    => 'DECIMAL(24,8)',
		];
	}

	/**
	 * Add a post status query arg.
	 *
	 * @param array           $query_args
	 * @param ClauseInterface $clause
	 * @param array           $valid_statuses Array of all valid statuses for the post type.
	 *
	 * @throws UnexpectedValueException When there is an error adding the query arg.
	 */
	protected function add_post_status_query_arg( &$query_args, $clause, $valid_statuses ) {
		switch ( $clause->get_operator() ) {
			case 'IN':
				$value = $clause->get_value();
				break;
			case 'NOT IN':
				// WP_Query doesn't support querying a post by the statuses it is not
				$value = array_diff( $valid_statuses, $clause->get_value() );
				break;
			default:
				throw new UnexpectedValueException();
		}

		$query_args['post_status'] = $value;
	}
}

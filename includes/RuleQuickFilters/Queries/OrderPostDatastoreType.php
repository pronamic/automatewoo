<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NumericClause;
use AutomateWoo\RuleQuickFilters\Clauses\OrClause;
use AutomateWoo\Rules\Order_Meta;
use UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderPostDatastoreType.
 *
 * @since   5.5.23
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
class OrderPostDatastoreType extends AbstractPostDatastoreType {

	/**
	 * Get the WP post type for the data type.
	 *
	 * @return string
	 */
	protected function get_post_type() {
		return 'shop_order';
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
		// Handle compound OR clauses by building a meta_query group with OR relation.
		if ( $clause instanceof OrClause ) {
			$this->add_or_clause_meta_query_arg( $query_args, $clause );
			return;
		}

		$property = $clause->get_property();

		// Address custom fields (flagged using the $property_prefix)
		if ( strpos( $property, Order_Meta::$property_prefix ) === 0 ) {
			$property = str_replace( Order_Meta::$property_prefix, '', $property );
			if ( $clause instanceof NumericClause ) {
				$this->add_decimal_post_meta_query_arg( $query_args, $property, $clause );
			} else {
				$this->add_basic_post_meta_query_arg( $query_args, $property, $clause );
			}
			return;
		}

		switch ( $property ) {
			case 'billing_city':
			case 'billing_country':
			case 'billing_email':
			case 'billing_phone':
			case 'billing_postcode':
			case 'billing_state':
			case 'created_via':
			case 'payment_method':
			case 'shipping_country':
			case 'shipping_state':
				$this->add_basic_post_meta_query_arg( $query_args, "_{$property}", $clause );
				break;
			case 'order_total':
				$this->add_decimal_post_meta_query_arg( $query_args, "_{$property}", $clause );
				break;
			case 'customer_user':
				$this->add_integer_post_meta_query_arg( $query_args, "_{$property}", $clause );
				break;
			case 'date_paid':
				$this->add_datetime_post_meta_query_arg( $query_args, "_{$property}", $clause, true );
				break;
			case 'date_created':
				$this->add_post_date_query_arg( $query_args, $clause );
				break;
			case 'status':
				$this->add_post_status_query_arg( $query_args, $clause, array_keys( wc_get_order_statuses() ) );
				break;
			case 'customer_note':
				$this->add_post_column_string_query_arg( 'post_excerpt', $clause );
				break;
			default:
				parent::map_clause_to_wp_query_arg( $clause, $query_args );
		}
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
		$args                = parent::get_default_wp_query_args( $number, $offset );
		$args['post_status'] = array_keys( wc_get_order_statuses() );

		return $args;
	}

	/**
	 * Add an OR clause to the meta_query args.
	 *
	 * Maps each sub-clause through the regular clause mapper and wraps supported
	 * meta_query entries in an OR group.
	 *
	 * @since 6.5.0
	 *
	 * @param array    $query_args The WP_Query args array.
	 * @param OrClause $clause     The compound OR clause.
	 *
	 * @throws UnexpectedValueException When a sub-clause cannot be mapped to one meta_query entry.
	 */
	protected function add_or_clause_meta_query_arg( &$query_args, OrClause $clause ) {
		$or_group = [ 'relation' => 'OR' ];

		foreach ( $clause->get_clauses() as $sub_clause ) {
			$sub_query_args = [ 'meta_query' => [] ];

			$this->map_clause_to_wp_query_arg( $sub_clause, $sub_query_args );

			if (
				array_keys( $sub_query_args ) !== [ 'meta_query' ] ||
				count( $sub_query_args['meta_query'] ) !== 1
			) {
				throw new UnexpectedValueException( 'OrClause only supports order post clauses that map to one meta_query entry.' );
			}

			$or_group[] = $sub_query_args['meta_query'][0];
		}

		$query_args['meta_query'][] = $or_group;
	}
}

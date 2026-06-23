<?php

namespace AutomateWoo\RuleQuickFilters;

use AutomateWoo\Integrations;
use AutomateWoo\RuleQuickFilters\Queries\OrderQuery;
use AutomateWoo\RuleQuickFilters\Queries\QueryInterface;
use AutomateWoo\RuleQuickFilters\Queries\SubscriptionQuery;
use Exception;

/**
 * Class QueryLoader.
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters
 */
final class QueryLoader {

	/**
	 * Load a quick filter query instance.
	 *
	 * @param array  $rule_data Rule data from a workflow.
	 * @param string $data_type The data type to query for.
	 *
	 * @return QueryInterface
	 *
	 * @throws Exception When quick filter can't be loaded.
	 */
	public static function load( $rule_data, $data_type ) {
		try {
			$clauses = ( new ClauseGenerator() )->generate( $rule_data, $data_type );

			switch ( $data_type ) {
				case 'order':
					return new OrderQuery( $clauses );
				case 'subscription':
					if ( Integrations::is_subscriptions_active() ) {
						return new SubscriptionQuery( $clauses );
					}
			}

			/**
			 * Filters custom quick filter query classes keyed by data type.
			 *
			 * @since 6.5.0
			 *
			 * @param array  $query_classes Query classes keyed by data type.
			 * @param string $data_type     The data type to query for.
			 * @param array  $rule_data     Rule data from a workflow.
			 */
			$query_classes = apply_filters( 'automatewoo/rule_quick_filters/query_classes', [], $data_type, $rule_data );

			if ( is_array( $query_classes ) && isset( $query_classes[ $data_type ] ) ) {
				$query_class = $query_classes[ $data_type ];

				if ( is_string( $query_class ) && class_exists( $query_class ) && is_subclass_of( $query_class, QueryInterface::class ) ) {
					return new $query_class( $clauses );
				}
			}
		} catch ( \Throwable $e ) {
			throw new Exception( esc_html__( 'There was an error loading the quick filter query.', 'automatewoo' ), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		throw new Exception( esc_html__( 'Quick filtering is not available for given data type.', 'automatewoo' ) );
	}
}

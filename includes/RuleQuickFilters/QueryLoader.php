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
		} catch ( Exception $e ) {
			throw new Exception( esc_html__( 'There was an error loading the quick filter query.', 'automatewoo' ), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		throw new Exception( esc_html__( 'Quick filtering is not available for given data type.', 'automatewoo' ) );
	}
}

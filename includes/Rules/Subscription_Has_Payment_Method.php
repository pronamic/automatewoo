<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\SetClause;
use AutomateWoo\Rules\Interfaces\QuickFilterable;

defined( 'ABSPATH' ) || exit;

/**
 * Subscription_Has_Payment_Method rule class.
 *
 * @since 4.4.3
 */
class Subscription_Has_Payment_Method extends Abstract_Bool implements QuickFilterable {

	/**
	 * Data item for the rule.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Has Payment Method', 'automatewoo' );
	}

	/**
	 * Validate the rule.
	 *
	 * @param \WC_Subscription $subscription
	 * @param string           $compare
	 * @param string           $value
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {
		$has = $subscription->has_payment_gateway();
		return $value === 'yes' ? $has : ! $has;
	}

	/**
	 * Get quick filter clause.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param mixed  $value
	 *
	 * @return ClauseInterface
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return new SetClause(
			'payment_method',
			$value === 'yes' ? 'SET' : 'NOT SET'
		);
	}
}

<?php

namespace AutomateWoo\Rules;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\StringClause;
use AutomateWoo\Rules\Interfaces\QuickFilterable;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Requires_Manual_Renewal
 */
class Subscription_Requires_Manual_Renewal extends Abstract_Bool implements QuickFilterable {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Requires Manual Renewal', 'automatewoo' );
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param string           $compare
	 * @param string           $value
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {
		$manual = $subscription->get_requires_manual_renewal();
		return $value === 'yes' ? $manual : ! $manual;
	}

	/**
	 * Get quick filter clause for this rule.
	 *
	 * @since 5.0.0
	 *
	 * @param string $compare_type
	 * @param array  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws \InvalidArgumentException When the value is invalid.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return new StringClause(
			'requires_manual_renewal',
			'=',
			$value === 'yes' ? 'true' : 'false'
		);
	}
}

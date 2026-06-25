<?php

namespace AutomateWoo;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\Rules\Interfaces\QuickFilterable;
use AutomateWoo\Rules\Utilities\ArrayQuickFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * @class Rule_Order_Payment_Gateway
 */
class Rule_Order_Payment_Gateway extends Rules\Preloaded_Select_Rule_Abstract implements QuickFilterable {

	use ArrayQuickFilter;

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Order - Payment Gateway', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		$choices = [];

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$choices[ $gateway->id ] = $this->get_gateway_choice_label( $gateway );
		}

		return $choices;
	}

	/**
	 * Get the select choice label for a payment gateway.
	 *
	 * Appends a "(disabled)" suffix to the gateway title when the gateway is disabled.
	 *
	 * @since 6.5.0
	 *
	 * @param \WC_Payment_Gateway $gateway Payment gateway instance.
	 * @return string
	 */
	protected function get_gateway_choice_label( \WC_Payment_Gateway $gateway ): string {
		if ( $this->is_gateway_enabled( $gateway ) ) {
			return $gateway->get_title();
		}

		/* translators: %s: payment gateway title */
		return sprintf( __( '%s (disabled)', 'automatewoo' ), $gateway->get_title() );
	}

	/**
	 * Check if a payment gateway is enabled.
	 *
	 * Handles both string ('yes') and boolean true values for the enabled property
	 * to ensure compatibility across gateway implementations.
	 *
	 * @since 6.5.0
	 *
	 * @param \WC_Payment_Gateway $gateway Payment gateway instance.
	 * @return bool
	 */
	protected function is_gateway_enabled( \WC_Payment_Gateway $gateway ): bool {
		$enabled = $gateway->enabled;
		if ( is_bool( $enabled ) ) {
			return $enabled;
		}
		return in_array( strtolower( (string) $enabled ), [ 'yes', 'true', '1' ], true );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 *
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_select( $order->get_payment_method(), $compare, $value );
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
	 *
	 * @throws Exception When there is an error.
	 */
	public function get_quick_filter_clause( $compare_type, $value ) {
		return $this->generate_array_quick_filter_clause( 'payment_method', $compare_type, $value );
	}
}

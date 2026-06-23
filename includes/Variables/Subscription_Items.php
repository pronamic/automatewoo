<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Items
 */
class Variable_Subscription_Items extends Variable_Abstract_Product_Display {

	/** @var bool */
	public $supports_order_table = true;

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		add_filter( 'automatewoo/variables/product_templates', [ $this, 'add_subscription_table_template' ] );
		parent::load_admin_details();
		remove_filter( 'automatewoo/variables/product_templates', [ $this, 'add_subscription_table_template' ] );

		$this->description = __( 'Displays a product listing of items in a subscription.', 'automatewoo' );
	}

	/**
	 * Add a subscription-specific product display template.
	 *
	 * @since 6.5.0
	 *
	 * @param array $templates Product display templates.
	 * @return array
	 */
	public function add_subscription_table_template( $templates ) {
		$templates['subscription-table'] = __( 'Subscription Table', 'automatewoo' );

		return $templates;
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @param Workflow         $workflow
	 * @return string
	 */
	public function get_value( $subscription, $parameters, $workflow ) {

		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;
		$items    = $subscription->get_items();
		$products = [];

		foreach ( $items as $item ) {
			$products[] = $item->get_product();
		}

		$args = array_merge(
			$this->get_default_product_template_args( $workflow, $parameters ),
			[
				'products'         => $products,
				'subscription'     => $subscription,
				'order'            => $subscription,
				// The shared product templates now hide fully refunded order items by default. Opt out for
				// subscriptions to keep showing every item (WC_Subscription::get_refunds() returns nothing anyway).
				'include_refunded' => true,
			]
		);

		return $this->get_product_display_html( $template, $args );
	}
}

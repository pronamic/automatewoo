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
		parent::load_admin_details();
		$this->description = __( 'Displays a product listing of items in a subscription.', 'automatewoo' );
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
				'products'     => $products,
				'subscription' => $subscription,
				'order'        => $subscription,
			]
		);

		return $this->get_product_display_html( $template, $args );
	}
}

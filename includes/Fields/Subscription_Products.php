<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This select field is limited to subscription products.
 * This generally shouldn't be used because any product can be used as a subscription.
 *
 * @class Subscription_Products
 */
class Subscription_Products extends Select {

	/** @var string  */
	protected $name = 'subscription_products';

	/** @var bool  */
	public $multiple = true;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( false );

		$this->set_title( __( 'Subscription products', 'automatewoo' ) );
		$this->set_placeholder( __( '[Any]', 'automatewoo' ) );

		$options = [];

		$query = new \WP_Query(
			[
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => [
					[
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [
							'subscription',
							'variable-subscription',
						],
					],
				],
			]
		);

		foreach ( $query->posts as $subscription_post ) {
			$product = wc_get_product( $subscription_post );

			$options[ $product->get_id() ] = $product->get_formatted_name();

			if ( $product->is_type( 'variable-subscription' ) ) {
				foreach ( $product->get_children() as $variation_id ) {
					$variation                = wc_get_product( $variation_id );
					$options[ $variation_id ] = $variation->get_formatted_name();
				}
			}
		}

		$this->set_options( $options );
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_value( $value ) {
		if ( $this->multiple ) {
			return Clean::ids( $value );
		} else {
			return Clean::id( $value );
		}
	}
}

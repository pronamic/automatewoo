<?php

namespace AutomateWoo\Admin;

use AutomateWoo\Customer;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Guest_Query;

/**
 * Class JSON_Search
 *
 * @since 4.5.2
 * @package AutomateWoo
 */
final class JSON_Search {

	/**
	 * Search for products and send JSON.
	 *
	 * It's more performant to define our own method for this special case, rather than using WC
	 * core's WC_AJAX::json_search_products() and attaching a callback to the results of it, which
	 * are passed through the 'woocommerce_json_search_found_products' filter. Because then we'd
	 * need to first remove variable products from that set, then run another query to find enough
	 * non-variable products to fill out the returned set up to the 'woocommerce_json_search_limit'
	 * value. Otherwise, it's possible we could return a set much smaller than that limit, or even
	 * an empty set when there are valid, matching products, but the first 30 matching products
	 * were all variable and removed.
	 *
	 * @see WC_AJAX::json_search_products()
	 *
	 * @param string $term               The search term.
	 * @param bool   $include_variations Include product variations in search?
	 * @param bool   $include_variables  Include variable products in search?
	 */
	public static function products( $term, $include_variations, $include_variables ) {
		if ( empty( $term ) ) {
			wp_die();
		}

		$product_ids = \WC_Data_Store::load( 'product' )->search_products( $term, '', (bool) $include_variations );
		$products    = [];
		$limit       = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || ! wc_products_array_filter_readable( $product ) ) {
				continue;
			}

			if ( ! $include_variables && $product->is_type( 'variable' ) ) {
				continue;
			}

			$products[ $product->get_id() ] = $product;

			if ( count( $products ) >= $limit ) {
				break;
			}
		}

		$results = [];

		foreach ( $products as $product ) {
			$results[ $product->get_id() ] = rawurldecode( sanitize_text_field( $product->get_formatted_name() ) );
		}

		wp_send_json( apply_filters( 'woocommerce_json_search_found_products', $results ) );
	}

	/**
	 * Search for workflows and send JSON.
	 *
	 * @param string $term
	 */
	public static function workflows( $term ) {
		if ( empty( $term ) ) {
			wp_die();
		}

		$args = [
			'post_type'        => 'aw_workflow',
			'post_status'      => 'any',
			'posts_per_page'   => 50,
			's'                => $term,
			'suppress_filters' => true,
			'no_found_rows'    => true,
		];

		$query   = new \WP_Query( $args );
		$results = [];

		foreach ( $query->posts as $post ) {
			$results[ $post->ID ] = rawurldecode( $post->post_title );
		}

		wp_send_json( $results );
	}

	/**
	 * Search customers, includes guests customers. Sends JSON.
	 *
	 * @param string $term
	 */
	public static function customers( $term ) {
		$found_customers = [];
		$results         = [];
		$limit           = 80;

		if ( 3 > strlen( $term ) ) {
			$limit = 20;
		}

		if ( empty( $term ) ) {
			wp_die();
		}

		$guest_query = new Guest_Query();
		$guest_query->where( 'email', "%$term%", 'LIKE' );
		$guest_query->set_limit( $limit );

		foreach ( $guest_query->get_results() as $guest ) {
			$found_customers[] = Customer_Factory::get_by_guest_id( $guest->get_id() );
		}

		$query = new \WP_User_Query(
			[
				'search'         => '*' . esc_attr( $term ) . '*',
				'search_columns' => [ 'user_login', 'user_email', 'user_nicename', 'display_name' ],
				'fields'         => 'ID',
				'number'         => $limit,
			]
		);

		$query2 = new \WP_User_Query(
			[
				'fields'     => 'ID',
				'number'     => $limit,
				'meta_query' => [
					'relation' => 'OR',
					[
						'key'     => 'first_name',
						'value'   => $term,
						'compare' => 'LIKE',
					],
					[
						'key'     => 'last_name',
						'value'   => $term,
						'compare' => 'LIKE',
					],
				],
			]
		);

		$user_ids = wp_parse_id_list( array_merge( $query->get_results(), $query2->get_results() ) );

		foreach ( $user_ids as $user_id ) {
			$found_customers[] = Customer_Factory::get_by_user_id( $user_id );
		}

		/**
		 * For IDE.
		 *
		 * @var Customer[] $found_customers
		 */
		$found_customers = array_filter( $found_customers );

		foreach ( $found_customers as $customer ) {
			$results[ $customer->get_id() ] = sprintf(
				/* translators: %1$s customer full name (optionally with guest label appended), %2$s customer email */
				esc_html__( '%1$s &ndash; %2$s', 'automatewoo' ),
				$customer->is_registered() ? $customer->get_full_name() : $customer->get_full_name() . ' ' . __( '[Guest]', 'automatewoo' ),
				$customer->get_email()
			);
		}

		wp_send_json( $results );
	}

	/**
	 * Search for workflows and send JSON.
	 *
	 * @param string $term
	 * @param bool   $exclude_personalized
	 * @param bool   $recurring_only
	 */
	public static function coupons( $term, $exclude_personalized, $recurring_only ) {
		if ( empty( $term ) ) {
			wp_die();
		}

		// WP_Query search arg is case insensitive
		$args = [
			'post_type'      => 'shop_coupon',
			'posts_per_page' => 50,
			'no_found_rows'  => true,
			'meta_query'     => [],
			's'              => $term,
		];

		if ( $exclude_personalized ) {
			$args['meta_query'][] = [
				'key'     => '_is_aw_coupon',
				'compare' => 'NOT EXISTS',
			];
		}

		if ( $recurring_only ) {
			$args['meta_query'][] = array(
				'key'   => 'discount_type',
				'value' => array(
					'recurring_fee',
					'recurring_percent',
				),
			);
		}

		$query   = new \WP_Query( $args );
		$results = [];

		foreach ( $query->posts as $coupon ) {
			$code             = wc_format_coupon_code( $coupon->post_title );
			$results[ $code ] = $code;
		}

		wp_send_json( $results );
	}

	/**
	 * Search for Sensei data (Courses, Lessons, Quizzes, Questions) and send JSON.
	 *
	 * @param string $term      The search term.
	 * @param string $post_type The post type to search for
	 *
	 * @since 5.6.10
	 */
	public static function sensei_data( $term, $post_type ) {
		if ( empty( $term ) ) {
			wp_die();
		}

		$args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'no_found_rows'  => true,
			's'              => $term,
		];

		$query   = new \WP_Query( $args );
		$results = [];

		foreach ( $query->posts as $data ) {
			$results[ $data->ID ] = $data->post_title;
		}

		wp_send_json( $results );
	}
}

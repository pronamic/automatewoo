<?php

defined( 'ABSPATH' ) || exit;


/**
 * Wrapper for wc_get_products()
 *
 * - Adds order by by popularity option
 * - Makes 'status' default to 'publish'
 *
 * @since 4.4.0
 *
 * @param array $args
 *
 * @return array|stdClass|WC_Product[]
 */
function aw_get_products( $args ) {
	add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'aw_filter_get_products_query_args', 10, 2 );

	$args = wp_parse_args(
		$args,
		[
			'status' => 'publish',
		]
	);

	$products = wc_get_products( $args );

	remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'aw_filter_get_products_query_args', 10 );

	return $products;
}


/**
 * Filters the WC get products query args.
 *
 * Adds orderby popularity option.
 *
 * @since 4.4.0
 *
 * @param array $query      Args for WP_Query.
 * @param array $query_vars Query vars from WC_Product_Query.
 *
 * @return array
 */
function aw_filter_get_products_query_args( $query, $query_vars ) {
	if ( $query_vars['orderby'] === 'popularity' ) {
		$query['meta_key'] = 'total_sales';
		$query['orderby']  = 'meta_value_num';
	}
	return $query;
}


/**
 * Function that returns an array containing the IDs of the recent products.
 *
 * @since 2.1.0
 *
 * @param int $limit
 * @return array
 */
function aw_get_recent_product_ids( $limit = -1 ) {
	$query = new WP_Query(
		[
			'post_type'           => 'product',
			'posts_per_page'      => $limit,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'desc',
			'fields'              => 'ids',
			'meta_query'          => WC()->query->get_meta_query(),
			'tax_query'           => WC()->query->get_tax_query(),
		]
	);
	return $query->posts;
}


/**
 * Function that returns an array containing the IDs of the recent products.
 *
 * @since 3.2.5
 *
 * @param int $limit
 * @return array
 */
function aw_get_top_selling_product_ids( $limit = -1 ) {
	$query = new WP_Query(
		[
			'post_type'           => 'product',
			'posts_per_page'      => $limit,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'meta_key'            => 'total_sales',
			'orderby'             => 'meta_value_num',
			'order'               => 'desc',
			'tax_query'           => WC()->query->get_tax_query(),
			'meta_query'          => WC()->query->get_meta_query(),
		]
	);

	return $query->posts;
}

/**
 * Filter fully refunded items from an array of order line items.
 *
 * @param \WC_Order_Item_Product[] $items
 * @param \WC_Order                $order
 *
 * @return \WC_Order_Item_Product[]
 *
 * @since 6.5.0
 */
function aw_filter_refunded_order_items( $items, $order ) {
	$filtered = [];

	foreach ( $items as $item_id => $item ) {
		$qty_refunded = abs( $order->get_qty_refunded_for_item( $item_id ) );

		if ( $qty_refunded < $item->get_quantity() ) {
			$filtered[ $item_id ] = $item;
		}
	}

	return $filtered;
}

/**
 * Filter hidden items from an array of order line items.
 *
 * Items are removed when they are marked as not visible via the
 * 'woocommerce_order_item_visible' filter, e.g. bundled items hidden
 * by WooCommerce Product Bundles.
 *
 * @param \WC_Order_Item_Product[] $items
 *
 * @return \WC_Order_Item_Product[]
 *
 * @since 6.5.0
 */
function aw_filter_hidden_bundled_order_items( $items ) {
	$filtered = [];

	foreach ( $items as $item_id => $item ) {
		if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
			$filtered[ $item_id ] = $item;
		}
	}

	return $filtered;
}

/**
 * Check whether a cart item is hidden from the cart.
 *
 * Uses the core 'woocommerce_cart_item_visible' filter, the same one cart
 * templates use, so items hidden by WooCommerce Product Bundles (both hidden
 * bundled children and hidden bundle containers) and other extensions are
 * respected.
 *
 * @param \AutomateWoo\Cart_Item $item
 *
 * @return bool
 *
 * @since 6.5.0
 */
function aw_is_hidden_bundled_cart_item( $item ) {
	return ! apply_filters( 'woocommerce_cart_item_visible', true, $item->get_data(), $item->get_key() );
}

/**
 * Remove unreviewable products from an array of product objects.
 *
 * @param \WC_Product[] $products
 *
 * @return \WC_Product[]
 *
 * @since 4.6.0
 */
function aw_get_reviewable_products( $products ) {
	$return = [];

	if ( ! is_array( $products ) ) {
		return [];
	}

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		// Replace variations with their parent product
		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );

			if ( $parent ) {
				// Deliberately replace duplicates
				$return[ $parent->get_id() ] = $parent;
			}
		} else {
			$return[ $product->get_id() ] = $product;
		}
	}

	return $return;
}

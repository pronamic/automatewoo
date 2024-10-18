<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/email/list-comma-separated.php
 *
 * @see https://automatewoo.com/docs/email/product-display-templates/
 *
 * @var \WC_Product[] $products
 * @var \WC_Order $order
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$links = [];

if ( isset( $order ) ) {
	$products = $order->get_items();
}

foreach( $products as $product ) {
	$filtered_permalink_data    = automatewoo_email_template_product_permalink( $product );
	$permalink                  = $filtered_permalink_data['permalink'];
	$filtered_product_name_data = automatewoo_email_template_product_name( $product );
	$product_name               = $filtered_product_name_data['product_name'];
	$links[]                    = '<a href="' . esc_url( $permalink ) .'">' . esc_attr( $product_name ) . '</a>';
}

echo implode( ', ', $links );

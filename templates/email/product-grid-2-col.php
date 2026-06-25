<?php

namespace AutomateWoo;

/**
 * Products items list
 *
 * Override this template by copying it to yourtheme/automatewoo/email/product-grid-2-col.php
 *
 * @see https://automatewoo.com/docs/email/product-display-templates/
 *
 * @var \WC_Product[] $products
 * @var \WC_Order $order
 * @var Workflow $workflow
 * @var string $variable_name
 * @var string $data_type
 * @var string $data_field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$n = 1;

?>

	<?php if ( is_array( $products ) ) : ?>

		<style>
			/** don't inline this css - hack for gmail */
			.aw-product-grid .aw-product-grid-item-2-col img {
				height: auto !important;
			}
		</style>

		<table cellspacing="0" cellpadding="0" class="aw-product-grid">
			<tbody><tr><td style="padding: 0;"><div class="aw-product-grid-container">

				<?php if ( isset( $order ) ) : ?>
					<?php $products = aw_filter_hidden_bundled_order_items( empty( $include_refunded ) ? aw_filter_refunded_order_items( $order->get_items(), $order ) : $order->get_items() ); ?>
				<?php endif; ?>

				<?php foreach ( $products as $product ) : ?>
					<?php $order_item = ( isset( $order ) && is_a( $product, 'WC_Order_Item_Product' ) ) ? $product : null; ?>
					<?php $filtered_permalink_data = automatewoo_email_template_product_permalink( $product ); ?>
					<?php $permalink = $filtered_permalink_data['permalink']; ?>
					<?php $filtered_product_name_data = automatewoo_email_template_product_name( $product ); ?>
					<?php $product_name = $filtered_product_name_data['product_name']; ?>
					<?php $product = $filtered_product_name_data['product']; ?>

					<div class="aw-product-grid-item-2-col" style="<?php echo ( $n % 2 ? '' : 'margin-right: 0;' ); ?>">

						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo \AW_Mailer_API::get_product_image( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_product_image() returns trusted <img> markup. ?></a>
						<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product_name ); ?></a></h3>
						<?php if ( isset( $order ) && $order_item ) : ?>
							<p class="price"><strong><?php echo wp_kses_post( automatewoo_email_template_line_total_html( $order, $order_item ) ); ?></strong></p>
						<?php else : ?>
							<p class="price"><strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong></p>
						<?php endif; ?>

					</div>

					<?php
					++$n;
endforeach;
				?>

			</div></td></tr></tbody>
		</table>

	<?php endif; ?>

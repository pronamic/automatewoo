<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Cart $cart
 */

$cart->calculate_totals();
$tax_display = get_option( 'woocommerce_tax_display_cart' );

?>

	<div class="automatewoo-modal__header">
		<h1><?php /* translators: Cart ID. */ printf( esc_html__( 'Cart #%s', 'automatewoo' ), esc_html( $cart->get_id() ) ); ?></h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">

			<?php if ( $cart->has_items() ) : ?>

				<table cellspacing="0" cellpadding="6" border="1" class="automatewoo-cart-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'automatewoo' ); ?></th>
						<th><?php esc_html_e( 'Quantity', 'automatewoo' ); ?></th>
						<th><?php esc_html_e( 'Price', 'automatewoo' ); ?></th>
					</tr>
					</thead>
					<tbody>

					<?php
					foreach ( $cart->get_items() as $item ) :
						$product    = $item->get_product();
						$line_total = $tax_display === 'excl' ? $item->get_line_subtotal() : $item->get_line_subtotal() + $item->get_line_subtotal_tax();

						?>

						<tr>
							<td>
								<?php if ( is_a( $product, 'WC_Product' ) && $product->is_purchasable() ) : ?>
									<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $item->get_name() ); ?></a>
									<br><?php echo $item->get_item_data_html( true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php else : ?>
									<?php echo wp_kses_post( $item->get_cart_item_name() ); ?> [<?php esc_html_e( 'deleted', 'automatewoo' ); ?>]
								<?php endif; ?>
							</td>
							<td><?php echo wp_kses_post( $item->get_quantity() ); ?></td>
							<td><?php echo wp_kses_post( $cart->price( $line_total ) ); ?></td>
						</tr>

					<?php endforeach; ?>

					</tbody>

					<tfoot>

					<?php if ( $cart->has_coupons() ) : ?>
						<tr>
							<th scope="row" colspan="2">
								<?php esc_html_e( 'Subtotal', 'automatewoo' ); ?>
								<?php if ( wc_tax_enabled() && 'excl' !== $tax_display ) : ?>
									<small><?php esc_html_e( '(incl. tax)', 'automatewoo' ); ?></small>
								<?php endif; ?>
							</th>
							<td><?php echo wp_kses_post( $cart->price( $cart->calculated_subtotal ) ); ?></td>
						</tr>
					<?php endif; ?>

					<?php
					foreach ( $cart->get_coupons() as $coupon_code => $coupon_data ) :
						$coupon_discount = $tax_display === 'excl' ? $coupon_data['discount_excl_tax'] : $coupon_data['discount_incl_tax'];
						?>
						<tr>
							<th scope="row" colspan="2"><?php /* translators: Coupon code. */ printf( esc_html__( 'Coupon: %s', 'automatewoo' ), esc_html( $coupon_code ) ); ?></th>
							<td><?php echo wp_kses_post( $cart->price( - $coupon_discount ) ); ?></td>
						</tr>
					<?php endforeach; ?>

					<?php if ( $cart->needs_shipping() ) : ?>
						<tr>
							<th scope="row" colspan="2"><?php esc_html_e( 'Shipping', 'automatewoo' ); ?></th>
							<td><?php echo wp_kses_post( $cart->get_shipping_total_html() ); ?></td>
						</tr>
					<?php endif; ?>

					<?php
					foreach ( $cart->get_fees() as $fee ) :
						$fee_amount = $tax_display === 'excl' ? $fee->amount : $fee->amount + $fee->tax;
						?>
						<tr>
							<th scope="row" colspan="2"><?php echo esc_html( $fee->name ); ?></th>
							<td><?php echo wp_kses_post( $cart->price( $fee_amount ) ); ?></td>
						</tr>
					<?php endforeach; ?>

					<?php if ( wc_tax_enabled() && $tax_display === 'excl' ) : ?>
						<tr>
							<th scope="row" colspan="2"><?php esc_html_e( 'Tax', 'automatewoo' ); ?></th>
							<td><?php echo wp_kses_post( $cart->price( $cart->calculated_tax_total ) ); ?></td>
						</tr>
					<?php endif; ?>

					<tr>
						<th scope="row" colspan="2">
							<?php esc_html_e( 'Total', 'automatewoo' ); ?>
							<?php if ( wc_tax_enabled() && $tax_display !== 'excl' ) : ?>
								<small><?php /* translators: Calculated tax total. */ printf( esc_html__( '(includes %s tax)', 'automatewoo' ), wp_kses_post( $cart->price( $cart->calculated_tax_total ) ) ); ?></small>
							<?php endif; ?>
						</th>
						<td><?php echo wp_kses_post( $cart->price( $cart->calculated_total ) ); ?></td>
					</tr>
					</tfoot>
				</table>

			<?php endif; ?>

			<ul>
				<li><strong><?php esc_html_e( 'Cart token', 'automatewoo' ); ?>:</strong> <?php echo esc_attr( $cart->get_token() ); ?></li>
				<li><strong><?php esc_html_e( 'Cart created', 'automatewoo' ); ?>:</strong> <?php echo esc_attr( Format::datetime( $cart->get_date_created(), 0 ) ); ?></li>
			</ul>

		</div>
	</div>

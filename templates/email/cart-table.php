<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Cart table. Can only be used with the cart.items variable
 *
 * Override this template by copying it to yourtheme/automatewoo/email/cart-table.php
 *
 * @see https://automatewoo.com/docs/email/product-display-templates/
 *
 * @var array $cart_items
 * @var Cart $cart
 * @var Workflow $workflow
 * @var string $variable_name
 * @var string $data_type
 * @var string $data_field
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$cart->calculate_totals();
$tax_display = get_option( 'woocommerce_tax_display_cart' );

?>

<?php if ( $cart->has_items() ): ?>

	<table cellspacing="0" cellpadding="6" border="1" class="aw-order-table">
		<thead>
		<tr>
			<th class="td" scope="col" colspan="2" style="text-align:left;"><?php _e( 'Product', 'automatewoo' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php _e( 'Quantity', 'automatewoo' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php _e( 'Price', 'automatewoo' ); ?></th>
		</tr>
		</thead>
		<tbody>

		<?php foreach ( $cart->get_items() as $item ):

			if ( ! $product = $item->get_product() ) {
				continue; // don't show items if there is no product
			}

			$line_total = $tax_display === 'excl' ? $item->get_line_subtotal() : $item->get_line_subtotal() + $item->get_line_subtotal_tax();

			?>

			<tr>
				<td width="115"><a href="<?php echo $product->get_permalink() ?>"><?php echo \AW_Mailer_API::get_product_image( $product, 'thumbnail' ) ?></a></td>
				<td>
                    <a href="<?php echo $product->get_permalink() ?>"><?php echo $item->get_name(); ?></a>
                   <?php echo $item->get_item_data_html( true ) ?>
                </td>
				<td><?php echo $item->get_quantity() ?></td>
				<td><?php echo $cart->price( $line_total ); ?></td>
			</tr>

		<?php endforeach; ?>

		</tbody>

		<tfoot>

			<?php if ( $cart->has_coupons() ): ?>
				<tr>
					<th scope="row" colspan="3">
						<?php _e('Subtotal', 'automatewoo'); ?>
						<?php if ( wc_tax_enabled() && 'excl' !== $tax_display ) : ?>
							<small><?php _e( '(incl. tax)','automatewoo' ) ?></small>
						<?php endif; ?>
					</th>
					<td><?php echo $cart->price( $cart->calculated_subtotal ); ?></td>
				</tr>
			<?php endif; ?>

			<?php foreach ( $cart->get_coupons() as $coupon_code => $coupon_data ):

				$coupon_discount = $tax_display === 'excl' ? $coupon_data['discount_excl_tax'] : $coupon_data['discount_incl_tax'];
				?>

				<tr>
					<th scope="row" colspan="3"><?php printf(__('Coupon: %s', 'automatewoo'), $coupon_code ); ?></th>
					<td><?php echo $cart->price( - $coupon_discount ); ?></td>
				</tr>
			<?php endforeach; ?>

            <?php if ( $cart->needs_shipping() ): ?>
                <tr>
                    <th scope="row" colspan="3"><?php _e( 'Shipping', 'automatewoo' ); ?></th>
                    <td><?php echo $cart->get_shipping_total_html(); ?></td>
                </tr>
            <?php endif; ?>

			<?php foreach ( $cart->get_fees() as $fee ):
					$fee_amount = $tax_display === 'excl' ? $fee->amount : $fee->amount + $fee->tax;
				?>
				<tr>
					<th scope="row" colspan="3"><?php echo esc_html( $fee->name ); ?></th>
					<td><?php echo $cart->price( $fee_amount ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( wc_tax_enabled() && $tax_display === 'excl' ): ?>
				<tr>
					<th scope="row" colspan="3"><?php _e( 'Tax', 'automatewoo' ); ?></th>
					<td><?php echo $cart->price( $cart->calculated_tax_total ); ?></td>
				</tr>
			<?php endif; ?>

			<tr>
				<th scope="row" colspan="3">
					<?php _e( 'Total', 'automatewoo' ); ?>
					<?php if ( wc_tax_enabled() && $tax_display !== 'excl' ): ?>
						<small><?php printf( __( '(includes %s tax)','automatewoo' ), $cart->price( $cart->calculated_tax_total ) ) ?></small>
					<?php endif; ?>
				</th>
				<td><?php echo $cart->price( $cart->calculated_total ); ?></td>
			</tr>
		</tfoot>
	</table>

<?php endif; ?>
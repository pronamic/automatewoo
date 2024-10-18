<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Products items list
 *
 * Override this template by copying it to yourtheme/automatewoo/email/product-rows.php
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

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<?php if ( is_array( $products ) ): ?>

	<table cellspacing="0" cellpadding="0" style="width: 100%;" class="aw-product-rows"><tbody>

		<?php if ( isset( $order ) ): ?>
			<?php $products = $order->get_items(); ?>
		<?php endif; ?>

		<?php foreach ( $products as $product ): ?>
			<?php $filtered_permalink_data    = automatewoo_email_template_product_permalink( $product ) ?>
			<?php $permalink                  = $filtered_permalink_data['permalink']; ?>
			<?php $filtered_product_name_data = automatewoo_email_template_product_name( $product ) ?>
			<?php $product_name               = $filtered_product_name_data['product_name']; ?>
			<?php $product                    = $filtered_product_name_data['product']; ?>
			<tr>

				<td class="image" width="25%">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo \AW_Mailer_API::get_product_image( $product ) ?></a>
				</td>

				<td>
					<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product_name ); ?></a></h3>
				</td>

				<td align="right" class="last" width="35%">
					<p class="price"><?php echo $product->get_price_html(); ?></p>
				</td>

			</tr>
		<?php endforeach; ?>

	</tbody></table>

<?php endif; ?>

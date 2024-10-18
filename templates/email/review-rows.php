<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Product display template: Review Product Rows
 *
 * Override this template by copying it to yourtheme/automatewoo/email/review-rows.php
 *
 * @see https://automatewoo.com/docs/email/product-display-templates/
 * @since 3.7
 *
 * @var \WC_Product[] $products
 * @var Workflow $workflow
 * @var string $variable_name
 * @var string $data_type
 * @var string $data_field
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$products = aw_get_reviewable_products( $products );

?>

<?php if ( is_array( $products ) ): ?>

	<table cellspacing="0" cellpadding="0" style="width: 100%;" class="aw-product-rows"><tbody>

		<?php foreach ( $products as $product ): ?>
			<tr>

				<td class="image" width="25%"><?php echo \AW_Mailer_API::get_product_image( $product ) ?></td>

				<td>
					<h3><?php echo esc_html( $product->get_name() ); ?></h3>
				</td>

				<td align="right" class="last" width="35%">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="automatewoo-button automatewoo-button--small"><?php esc_html_e( 'Leave a review', 'automatewoo' ); ?></a>
				</td>

			</tr>
		<?php endforeach; ?>

	</tbody></table>

<?php endif; ?>
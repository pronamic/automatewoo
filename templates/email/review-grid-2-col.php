<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Product display template: Review Product Grid - 2 Column
 *
 * Override this template by copying it to yourtheme/automatewoo/email/review-grid-2-col.php
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

$n        = 1;
$products = aw_get_reviewable_products( $products );

?>

	<?php if ( is_array( $products ) ): ?>

		<style>
			/** don't inline this css - hack for gmail */
			.aw-product-grid .aw-product-grid-item-2-col img {
				height: auto !important;
			}
		</style>

		<table cellspacing="0" cellpadding="0" class="aw-product-grid aw-reviews-grid">
			<tbody><tr><td style="padding: 0;"><div class="aw-product-grid-container">

					<?php foreach ( $products as $product ): ?>

						<div class="aw-product-grid-item-2-col aw-reviews-grid__item" style="<?php echo ( $n % 2 ? '' : 'margin-right: 0;' ) ?>">

							<?php echo \AW_Mailer_API::get_product_image( $product ) ?>
							<h3><?php echo esc_html( $product->get_name() ); ?></h3>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="automatewoo-button"><?php esc_html_e( 'Leave a review', 'automatewoo' ); ?></a>

						</div>

						<?php $n++; endforeach; ?>

				</div></td></tr></tbody>
		</table>

	<?php endif; ?>
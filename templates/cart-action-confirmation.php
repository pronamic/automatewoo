<?php
/**
 * Confirmation page shown before an emailed reorder / restore-cart link changes the cart.
 *
 * Override this template by copying it to yourtheme/automatewoo/cart-action-confirmation.php
 *
 * @var bool   $is_available Whether the linked order / saved cart can still be acted on.
 * @var string $heading      Confirmation question shown to the visitor.
 * @var string $description  Short explanation of what confirming will do.
 * @var string $button_label Label for the confirm button.
 * @var string $nonce_name   Nonce action name embedded in the confirmation form.
 * @var string $cart_url     Cart page URL for the cancel / fallback link.
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="aw-cart-action-confirmation woocommerce">

	<?php wc_print_notices(); ?>

	<h2 class="aw-cart-action-confirmation__heading"><?php echo esc_html( $heading ); ?></h2>

	<?php if ( $is_available ) : ?>

		<p class="aw-cart-action-confirmation__description"><?php echo esc_html( $description ); ?></p>

		<?php // Posting to the current URL preserves the action, its identifier and any tracking args, on both pretty and plain permalinks. ?>
		<form action="" method="post" class="aw-cart-action-confirmation__form">
			<?php wp_nonce_field( $nonce_name ); ?>
			<p>
				<button type="submit" class="woocommerce-Button button aw-cart-action-confirmation__submit">
					<?php echo esc_html( $button_label ); ?>
				</button>
			</p>
			<p>
				<a href="<?php echo esc_url( $cart_url ); ?>" class="aw-cart-action-confirmation__cancel">
					<?php esc_html_e( 'No thanks, go to cart', 'automatewoo' ); ?>
				</a>
			</p>
		</form>

	<?php else : ?>

		<p class="aw-cart-action-confirmation__description">
			<?php esc_html_e( 'This link is no longer available.', 'automatewoo' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $cart_url ); ?>" class="woocommerce-Button button">
				<?php esc_html_e( 'Go to cart', 'automatewoo' ); ?>
			</a>
		</p>

	<?php endif; ?>

</div>

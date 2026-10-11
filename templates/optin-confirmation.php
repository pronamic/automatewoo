<?php
/**
 * Opt-in confirmation page for a communication signup.
 *
 * Override this template by copying it to yourtheme/automatewoo/optin-confirmation.php
 *
 * @var string $state      One of 'confirm', 'confirmed' or 'invalid'.
 * @var string $nonce_name Nonce action name for the confirm form.
 * @var string $home_url   Home URL for the fallback link.
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="aw-optin-confirmation woocommerce">

	<?php wc_print_notices(); ?>

	<?php if ( 'confirm' === $state ) : ?>

		<h2 class="aw-optin-confirmation__heading"><?php esc_html_e( 'Confirm your subscription', 'automatewoo' ); ?></h2>
		<p class="aw-optin-confirmation__description"><?php esc_html_e( 'Please confirm that you want to subscribe.', 'automatewoo' ); ?></p>

		<?php // Posting to the current URL keeps the action, key and token, on both pretty and plain permalinks. ?>
		<form action="" method="post" class="aw-optin-confirmation__form">
			<?php wp_nonce_field( $nonce_name ); ?>
			<button type="submit" class="woocommerce-Button button aw-optin-confirmation__submit">
				<?php esc_html_e( 'Confirm subscription', 'automatewoo' ); ?>
			</button>
		</form>

	<?php elseif ( 'confirmed' === $state ) : ?>

		<h2 class="aw-optin-confirmation__heading"><?php esc_html_e( 'Your subscription is confirmed', 'automatewoo' ); ?></h2>
		<p class="aw-optin-confirmation__description"><?php esc_html_e( 'Thanks! Your subscription has been confirmed.', 'automatewoo' ); ?></p>
		<p>
			<a href="<?php echo esc_url( $home_url ); ?>" class="woocommerce-Button button"><?php esc_html_e( 'Continue', 'automatewoo' ); ?></a>
		</p>

	<?php else : ?>

		<h2 class="aw-optin-confirmation__heading"><?php esc_html_e( 'This link is invalid or has expired', 'automatewoo' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( $home_url ); ?>" class="woocommerce-Button button"><?php esc_html_e( 'Continue', 'automatewoo' ); ?></a>
		</p>

	<?php endif; ?>

</div>

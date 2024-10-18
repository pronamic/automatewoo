<?php
/**
 * @var string $message
 * @var string $strong (optional)
 * @var string $type (optional)
 * @var string $class (optional)
 * @var string $notice_identifier (optional)   Used in async "remove notice" call.
 * @var string $button_text (optional)
 * @var string $button_link (optional)
 * @var string $button_class (optional)
 */
namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

if ( empty( $message ) ) {
	return;
}

?>
<div
	class="automatewoo-notice notice notice-<?php echo isset( $type ) ? esc_attr( $type ) : 'gray'; ?> <?php echo isset( $class ) ? esc_attr( $class ) : ''; ?>"
	<?php if ( isset( $notice_identifier ) ) : ?>
	data-automatewoo-dismissible-notice="<?php echo esc_attr( $notice_identifier ); ?>"
	<?php endif; ?>
>
	<p>
		<?php if ( ! empty( $strong ) ) : ?>
			<strong><?php echo wp_kses_post( $strong ); ?></strong>
		<?php endif; ?>

		<?php echo wp_kses_post( $message ); ?>
	</p>
	<?php if ( ! empty( $button_text ) && ! empty( $button_link ) ) : ?>
		<p><a href="<?php echo esc_url( $button_link ); ?>"
			class="button-primary <?php echo isset( $button_class ) ? esc_attr( $button_class ) : ''; ?>"
		><?php echo esc_html( $button_text ); ?></a></p>
	<?php endif; ?>
</div>

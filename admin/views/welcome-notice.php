<?php

/**
 * @var string $notice_identifier REQUIRED
 * @var string $title
 * @var string $class
 * @var string $description
 * @var array  $links array of arrays containing attributes href, class, text and data_link_type
 */

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

?>

<div class="notice automatewoo-welcome-notice is-dismissible <?php echo isset( $class ) ? esc_attr( $class ) : ''; ?>"
	data-automatewoo-dismissible-notice="<?php echo esc_attr( $notice_identifier ); ?>">
	<h3 class="automatewoo-welcome-notice__heading">
		<?php if ( ! empty( $title ) ) : ?>
			<?php echo wp_kses_post( $title ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Welcome to AutomateWoo', 'automatewoo' ); ?>
		<?php endif; ?>
	</h3>
	<div class="automatewoo-welcome-notice__text">
		<?php if ( ! empty( $description ) ) : ?>
			<p>
				<?php echo wp_kses_post( $description ); ?>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $links ) ) : ?>
			<p class="automatewoo-welcome-notice__links">
				<?php foreach ( $links as $link ) : ?>
					<a target="<?php echo isset( $link['target'] ) ? esc_attr( $link['target'] ) : '_blank'; ?>" href="<?php echo esc_url( $link['href'] ); ?>" class="<?php echo esc_attr( $link['class'] ); ?>"
					<?php echo isset( $link['data_link_type'] ) ? 'data-automatewoo-link-type="' . esc_attr( $link['data_link_type'] ) . '"' : ''; ?>
					>
						<?php echo esc_html( $link['text'] ); ?>
					</a>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
	</div>
	<div class="automatewoo-welcome-notice__image"></div>
</div>

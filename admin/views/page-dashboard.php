<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var array  $widgets
 * @var string $date_text
 * @var string $date_current
 * @var array  $date_tabs
 */

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--dashboard">

	<div class="automatewoo-dashboard-header">
		<h1><?php esc_html_e( 'Dashboard', 'automatewoo' ); ?></h1>

		<div class="automatewoo-dashboard-date-nav">
			<?php foreach ( $date_tabs as $date_tab_key => $date_tab_text ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'date', $date_tab_key, AutomateWoo\Admin::page_url( 'dashboard' ) ) ); ?>"
					class="components-button automatewoo-dashboard-date-nav__tab <?php echo ( $date_tab_key === $date_current ? 'automatewoo-dashboard-date-nav__tab--current' : '' ); ?>">
					<?php echo esc_attr( $date_tab_text ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php
		// Indicate the anchor for WP core to append the notices.
		// Ref: https://github.com/WordPress/wordpress-develop/blob/6.1/src/js/_enqueues/admin/common.js#L1081-L1090
	?>
	<hr class="wp-header-end">

	<div class="automatewoo-dashboard-widgets">
		<div class="automatewoo-dashboard-widget-sizer"></div>
		<?php foreach ( $widgets as $widget ) : ?>
			<?php $widget->output(); ?>
		<?php endforeach; ?>
	</div>

</div>


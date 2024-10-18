<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var AutomateWoo\Admin_Settings_Tab_Abstract   $current_tab
 * @var AutomateWoo\Admin_Settings_Tab_Abstract[] $tabs
 */

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--settings">

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $nav_tab ) : ?>
			<a href="<?php echo esc_url( $nav_tab->get_url() ); ?>" class="nav-tab <?php echo ( $current_tab->id === $nav_tab->id ? 'nav-tab-active' : '' ); ?>"><?php echo wp_kses_post( $nav_tab->name ); ?></a>
		<?php endforeach; ?>
	</h2>

	<div class="aw-settings-messages">
		<?php $current_tab->output_messages(); ?>
	</div>

	<div class="aw-settings-tab-container">

		<?php if ( $current_tab->show_tab_title ) : ?>
			<h3><?php echo wp_kses_post( $current_tab->name ); ?></h3>
		<?php endif; ?>

		<?php $current_tab->output(); ?>
	</div>

</div>


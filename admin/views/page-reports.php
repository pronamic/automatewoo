<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var AW_Admin_Reports_Tab_Abstract             $current_tab
 * @var AutomateWoo\Admin\Controllers\Reports     $controller
 * @var AutomateWoo\Admin_Settings_Tab_Abstract[] $tabs
 */

$html = $current_tab->output_before_report();

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--reports">

	<h1><?php /* translators: Tab name. */ printf( esc_html__( '%s Report', 'automatewoo' ), wp_kses_post( $current_tab->name ) ); ?></h1>

	<?php $controller->output_messages(); ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $nav_tab ) : ?>
			<a href="<?php echo esc_url( $nav_tab->get_url() ); ?>" class="nav-tab <?php echo ( $current_tab->id === $nav_tab->id ? 'nav-tab-active' : '' ); ?>"><?php echo wp_kses_post( $nav_tab->name ); ?></a>
		<?php endforeach; ?>
	</h2>

	<?php if ( $html ) : ?>
		<div class="aw-before-report-output">
			<?php echo wp_kses_post( $html ); ?>
		</div>
	<?php endif; ?>

	<div class="aw-reports-tab-container">
		<?php $current_tab->output(); ?>
	</div>

</div>


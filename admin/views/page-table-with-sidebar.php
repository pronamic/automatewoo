<?php
/**
 * @package AutomateWoo/Admin/Views
 * @since 2.7.8
 *
 * @var string                             $page
 * @var string                             $sidebar_content
 * @var string                             $messages
 * @var AutomateWoo\Admin\Controllers\Base $controller
 * @var AutomateWoo\Admin_List_Table       $table
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--<?php echo sanitize_html_class( $page ); ?>">

	<?php $controller->output_view( 'page-heading' ); ?>

	<?php echo wp_kses_post( $messages ); ?>

	<div class="automatewoo-content automatewoo-content--has-sidebar">

		<?php if ( isset( $sidebar_content ) ) : ?>
			<div class="automatewoo-sidebar">
				<?php echo wp_kses_post( $sidebar_content ); ?>
			</div>
		<?php endif; ?>

		<div class="automatewoo-main">
			<?php $table->display(); ?>
		</div>

	</div>

</div>

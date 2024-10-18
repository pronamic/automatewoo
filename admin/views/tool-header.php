<?php

namespace AutomateWoo;

/**
 * @var Tool_Abstract                      $tool
 * @var Admin\Controllers\Tools_Controller $controller
 */

defined( 'ABSPATH' ) || exit;

?>
<?php if ( ! WC()->is_wc_admin_active() ) : ?>
	<h1><a href="<?php echo esc_url( Admin::page_url( 'tools' ) ); ?>"><?php echo esc_html( $controller->get_heading() ); ?></a> &gt; <?php echo esc_html( $tool->title ); ?></h1>
<?php else : ?>
	<h1><?php echo esc_html( $tool->title ); ?></h1>
<?php endif; ?>

<?php $controller->output_messages(); ?>

<?php echo wp_kses_post( wpautop( $tool->description ) ); ?>

<?php if ( $tool->additional_description ) : ?>
	<?php echo wp_kses_post( wpautop( $tool->additional_description ) ); ?>
<?php endif; ?>

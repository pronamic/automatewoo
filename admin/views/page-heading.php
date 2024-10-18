<?php


defined( 'ABSPATH' ) || exit;

/**
 * @var AutomateWoo\Admin\Controllers\Base $controller
 */

?>

<h1 class="wp-heading-inline"><?php echo esc_attr( $controller->get_heading() ); ?></h1>

<?php foreach ( $controller->get_heading_links() as $heading_link => $heading_title ) : ?>
	<a href="<?php echo esc_url( $heading_link ); ?>" class="page-title-action"><?php echo esc_attr( $heading_title ); ?></a>
<?php endforeach; ?>

<hr class="wp-header-end">

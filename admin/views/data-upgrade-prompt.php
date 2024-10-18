<?php
/**
 * @since 2.1.0
 *
 * @var string $plugin_name
 * @var string $plugin_slug
 */

defined( 'ABSPATH' ) || exit;

if ( AutomateWoo\Installer::is_data_update_screen() ) {
	return;
}

AutomateWoo\Admin::notice(
	'info',
	/* translators: Plugin name. */
	sprintf( __( '%s database update required', 'automatewoo' ), $plugin_name ),
	__( '- Please run the updater as soon as possible. It is normal for this to take some time to complete.', 'automatewoo' ),
	'',
	__( 'Run the updater', 'automatewoo' ),
	add_query_arg(
		[
			'page'        => 'automatewoo-data-upgrade',
			'plugin_slug' => $plugin_slug,
		],
		admin_url( 'admin.php' )
	),
	'js-automatewoo-do-database-update'
);
?>

<script type="text/javascript">
	(function($) {
		$('.js-automatewoo-do-database-update').on('click', function(){
			return confirm("<?php echo esc_js( __( 'It is recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'automatewoo' ) ); ?>");
		});
	})(jQuery);
</script>

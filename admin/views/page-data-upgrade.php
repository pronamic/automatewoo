<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

$plugin_slug = Clean::string( aw_request( 'plugin_slug' ) );

if ( $plugin_slug === AW()->plugin_slug ) {
	// updating the primary plugin
	$plugin_name      = 'AutomateWoo';
	$version          = AW()->version;
	$update_available = Installer::is_database_upgrade_required();
} elseif ( $plugin_slug ) {
	// updating an addon
	$addon = Addons::get( $plugin_slug );

	if ( ! $addon ) {
		wp_die( esc_html__( 'Add-on could not be updated', 'automatewoo' ) );
	}

	$plugin_name      = $addon->name;
	$version          = $addon->version;
	$update_available = $addon->is_database_upgrade_available();
} else {
	wp_die( 'Missing parameter.' );
}



?>

<div id="automatewoo-upgrade-wrap" class="wrap woocommerce automatewoo-page automatewoo-page--data-upgrade">

	<h2><?php /* translators: Plugin name. */ printf( esc_html__( '%s - Database Update', 'automatewoo' ), esc_html( $plugin_name ) ); ?></h2>

	<?php if ( $update_available ) : ?>

		<p><?php esc_html_e( 'Reading update tasks...', 'automatewoo' ); ?></p>

		<p class="show-on-ajax">
			<?php /* translators: Version. */ printf( esc_html__( 'Upgrading data to version %s.', 'automatewoo' ), esc_html( $version ) ); ?>
			<span style="display: none" data-automatewoo-update-items-to-process-text>
				<?php /* translators: %1$s wrapping span tag, %2$s closing span tag */ printf( esc_html__( 'Approximately %1$s0%2$s items to process.', 'automatewoo' ), '<span data-automatewoo-update-items-to-process-count>', '</span>' ); ?>
			</span>
			<span style="display: none" data-automatewoo-update-items-processed-text>
				<?php /* translators: %1$s wrapping span tag, %2$s closing span tag */ printf( esc_html__( '%1$s0%2$s items processed.', 'automatewoo' ), '<span data-automatewoo-update-items-processed-count>', '</span>' ); ?>
			</span>
			<i class="automatewoo-upgrade-loader"></i>
		</p>

		<p class="show-on-complete"><?php esc_html_e( 'Database update complete', 'automatewoo' ); ?>.</p>

		<style type="text/css">

			/* hide show */
			.show-on-ajax,
			.show-on-complete {
				display: none;
			}

		</style>

		<script type="text/javascript">
		(function($) {

			var $wrap = $('#automatewoo-upgrade-wrap');

			var updater = {

				items_processed_count: 0,

				items_to_process_count: 0,

				init: function(){
					// allow user to read message for 1 second
					setTimeout(function(){
						updater.load_items_to_process_count(function() {
							updater.start();
						});
					}, 1000);
				},

				start: function(){
					$('.show-on-ajax').show();
					updater.maybe_reveal_items_to_process_text();
					updater.dispatch();
				},

				dispatch: function() {

					$.ajax({
						method: 'POST',
						url: ajaxurl,
						data: {
							action: 'aw_database_update',
							nonce: '<?php echo esc_js( wp_create_nonce( 'automatewoo_database_upgrade' ) ); ?>',
							plugin_slug: '<?php echo esc_js( $plugin_slug ); ?>'
						},
						success: function (response) {

							if ( response.success ) {

								if ( response.data.items_processed ) {
									updater.update_count( response.data.items_processed );
								}

								if ( response.data.complete ) {
									updater.complete();
								}
								else {
									updater.dispatch();
								}
							}
							else {
								updater.error( response );
							}

						}
					});

				},

				update_count: function( count ) {
					updater.items_processed_count += count;

					if ( updater.items_processed_count ) {
						$('[data-automatewoo-update-items-processed-text]').show();
					}

					$('[data-automatewoo-update-items-processed-count]').text( updater.items_processed_count );
				},

				/**
				 * Does ajax request to get number of items that need processing.
				 */
				load_items_to_process_count: function( callback ) {
					$.post( ajaxurl,
						{
							action: 'aw_database_update_items_to_process_count',
							plugin_slug: '<?php echo esc_js( $plugin_slug ); ?>',
							nonce: '<?php echo esc_js( wp_create_nonce( 'aw_database_update_items_to_process_count' ) ); ?>'
						},
						function( response ) {
							if ( response.success ) {
								updater.items_to_process_count = response.data.items_to_process;
							}
							callback();
						}
					);
				},

				maybe_reveal_items_to_process_text: function() {
					if ( updater.items_to_process_count === 0 ) {
						return;
					}
					$('[data-automatewoo-update-items-to-process-text]').show();
					$('[data-automatewoo-update-items-to-process-count]').text( updater.items_to_process_count );
				},

				complete: function() {
					$('.show-on-complete').show();
					$('.automatewoo-upgrade-loader').hide();
				},

				error: function( response ) {
					if ( response.data ) {
						$wrap.append('<p><strong>' + response.data + '</strong></p>');
					}
					$('.automatewoo-upgrade-loader').hide();
				}
			};

			updater.init();

		})(jQuery);
		</script>

	<?php else : ?>

		<p><?php esc_html_e( 'No updates available', 'automatewoo' ); ?>.</p>

	<?php endif; ?>

</div>

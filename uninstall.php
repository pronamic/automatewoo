<?php
/**
 * AutomateWoo Uninstaller.
 *
 * Removes all AutomateWoo data when the plugin is deleted,
 * if the "Remove data on uninstall" option is enabled.
 *
 * @since x.x.x
 * @package AutomateWoo
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Only proceed if the user has opted in to data removal.
 *
 * The option is stored as 'yes'/'no' string.
 */
if ( get_option( 'automatewoo_delete_data_on_uninstall' ) !== 'yes' ) {
	return;
}

// The plugin is not bootstrapped during uninstall, so load the autoloader
// to reach the self-contained Data_Cleaner class.
require_once __DIR__ . '/vendor/autoload.php';

AutomateWoo\Data_Cleaner::delete_all();

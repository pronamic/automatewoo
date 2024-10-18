<?php
namespace AutomateWoo;

use AutomateWoo\DatabaseUpdates\AbstractDatabaseUpdate;

/**
 * @class Installer
 */
class Installer {

	/** @var array */
	public static $db_updates = [
		'2.6.0',
		'2.6.1',
		'2.7.0',
		'2.9.7',
		'3.0.0',
		'3.5.0',
		'3.6.0',
		'4.0.0',
		'5.0.0',
		'5.1.0',
		'5.3.0',
		'6.0.0',
	];

	/** @var int  */
	public static $db_update_items_processed = 0;


	/**
	 * Initialize installer.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ], 5 );
		add_filter( 'plugin_action_links_' . AW()->plugin_basename, [ __CLASS__, 'plugin_action_links' ] );
	}


	/**
	 * Admin init
	 */
	public static function admin_init() {

		if ( defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		self::check_if_plugin_files_updated();

		if ( Options::database_version() !== AW()->version ) {
			self::install();

			if ( ! Options::database_version() ) {
				self::first_install();
			}

			// check for required database update
			if ( self::is_database_upgrade_required() ) {
				if ( apply_filters( 'woocommerce_enable_auto_update_db', false ) ) {
					self::run_database_updates();
				} else {
					add_action( 'admin_notices', [ __CLASS__, 'data_upgrade_prompt' ] );
				}
			} else {
				self::update_database_version( AW()->version );
				self::do_plugin_updated_actions();
			}
		}

		foreach ( Addons::get_all() as $addon ) {
			$addon->check_version();
		}
	}


	/**
	 * Checks and logs if plugin files have been updated.
	 *
	 * @since 4.3.0
	 */
	public static function check_if_plugin_files_updated() {
		$file_version = Options::file_version();

		if ( $file_version !== AW()->version ) {
			$old_version = $file_version;
			$new_version = AW()->version;

			if ( $old_version ) {
				Logger::info( 'updates', sprintf( 'AutomateWoo - Plugin updated from %s to %s', $old_version, $new_version ) );
			}

			update_option( 'automatewoo_file_version', $new_version, true );

			do_action( 'automatewoo_version_changed', $old_version, $new_version );
		}
	}

	/**
	 * Install
	 */
	public static function install() {
		Database_Tables::install_tables();
		self::create_pages();

		do_action( 'automatewoo_installed' );
	}

	/**
	 * Runs when AW is installed for the first time.
	 *
	 * On the other hand `Installer::install()` runs on every plugin update.
	 *
	 * @since 4.7.0
	 */
	private static function first_install() {
		do_action( 'automatewoo_first_installed' );
	}

	/**
	 * @return bool
	 */
	public static function is_database_upgrade_required() {
		if ( Options::database_version() === AW()->version ) {
			return false;
		}

		return Options::database_version() && version_compare( Options::database_version(), end( self::$db_updates ), '<' );
	}


	/**
	 * @return array
	 */
	public static function get_required_database_updates() {

		$required_updates = [];

		foreach ( self::$db_updates as $version ) {
			if ( version_compare( Options::database_version(), $version, '<' ) ) {
				$required_updates[] = $version;
			}
		}

		return $required_updates;
	}


	/**
	 * Handle updates, may be called multiple times to batch complete
	 * Returns false if updates are still required
	 *
	 * @return bool
	 */
	public static function run_database_updates() {
		wp_raise_memory_limit( 'admin' );

		$required_updates                = self::get_required_database_updates();
		self::$db_update_items_processed = 0; // reset counter

		// update one version at a time
		$update   = current( $required_updates );
		$complete = self::run_database_update( $update );

		if ( count( $required_updates ) > 1 ) {
			$complete = false; // not complete if there is more than one update
		}

		if ( $complete ) {
			self::do_plugin_updated_actions();
		}

		return $complete;
	}


	/**
	 * Return true if update is complete, return false if another pass is required
	 *
	 * @param string $version
	 * @return bool
	 */
	public static function run_database_update( $version ) {

		$update_file = AW()->path( "/includes/DatabaseUpdates/$version.php" );

		// recent updates will return a class
		$update = include $update_file; // nosemgrep No user input here

		if ( $update instanceof AbstractDatabaseUpdate ) {
			$update->dispatch_process();
			self::$db_update_items_processed += $update->get_items_processed_count();

			$complete = $update->is_complete();
		} else {
			// don't check completion on legacy updates
			$complete = true;
		}

		if ( $complete ) {
			self::update_database_version( $version );
		}

		return $complete;
	}


	/**
	 * Returns the item to process count for all currently required updates.
	 *
	 * @return int
	 */
	public static function get_database_update_items_to_process_count() {
		$required_updates = self::get_required_database_updates();
		$count            = 0;

		foreach ( $required_updates as $version ) {
			if ( version_compare( $version, '2.7.0', '<=' ) ) {
				continue; // old updates don't extend AbstractDatabaseUpdate class
			}

			$update_file = AW()->path( "/includes/DatabaseUpdates/$version.php" );
			$update      = include $update_file; /** @var $update AbstractDatabaseUpdate */
			$count      += $update->get_items_to_process_count();
		}

		return $count;
	}


	/**
	 * Update version to current
	 *
	 * @param string $version
	 */
	private static function update_database_version( $version ) {
		update_option( 'automatewoo_version', $version, true );
	}


	/**
	 * Renders prompt notice for user to update
	 */
	public static function data_upgrade_prompt() {
		Admin::get_view(
			'data-upgrade-prompt',
			[
				'plugin_name' => __( 'AutomateWoo', 'automatewoo' ),
				'plugin_slug' => AW()->plugin_slug,
			]
		);
	}


	/**
	 * @return bool
	 */
	public static function is_data_update_screen() {
		$screen = get_current_screen();
		return $screen->id === 'automatewoo_page_automatewoo-data-upgrade';
	}


	/**
	 * Show action links on the plugin screen.
	 *
	 * @param  mixed $links Plugin Action links
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = [
			'settings'      => '<a href="' . esc_url( Admin::page_url( 'settings' ) ) . '" title="' . esc_attr( __( 'View AutomateWoo Settings', 'automatewoo' ) ) . '">' . esc_html__( 'Settings', 'automatewoo' ) . '</a>',
			'documentation' => '<a href="' . esc_url( Admin::page_url( 'documentation' ) ) . '" title="' . esc_attr( __( 'View AutomateWoo Documentation', 'automatewoo' ) ) . '">' . esc_html__( 'Documentation', 'automatewoo' ) . '</a>',
		];

		return array_merge( $action_links, $links );
	}


	/**
	 * Run plugin updated actions.
	 */
	public static function do_plugin_updated_actions() {
		do_action( 'automatewoo_updated' );
		AW()->action_scheduler()->enqueue_async_action( 'automatewoo_updated_async' );

		// Queue the requirements changes notice to show (if necessary).
		AdminNotices::add_notice( 'requirements_changes' );
	}


	/**
	 * Creates required pages, run on every update, so it can be repeated without creating duplicates
	 */
	public static function create_pages() {

		$created_pages = get_option( '_automatewoo_created_pages', [] );

		$pages = apply_filters(
			'automatewoo_create_pages',
			[
				'communication_preferences' => [
					'name'    => _x( 'communication-preferences', 'Page slug', 'automatewoo' ),
					'title'   => _x( 'Communication preferences', 'Page title', 'automatewoo' ),
					'content' => '[automatewoo_communication_preferences]',
					'option'  => 'automatewoo_communication_preferences_page_id',
				],
			]
		);

		foreach ( $pages as $key => $page ) {
			if ( in_array( $key, $created_pages, true ) ) {
				continue;
			}

			Admin::create_page( esc_sql( $page['name'] ), $page['title'], $page['content'], $page['option'] );
			$created_pages[] = $key;
		}

		update_option( '_automatewoo_created_pages', $created_pages, false );
	}
}

<?php

namespace AutomateWoo;

use AutomateWoo\Admin\Analytics;
use AutomateWoo\Admin\AssetData;
use AutomateWoo\Admin\WCAdminConnectPages;
use AutomateWoo\HPOS_Helper;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;

/**
 * @class Admin
 */
class Admin {

	/**
	 * Initialize the Admin class.
	 */
	public static function init() {
		$self = __CLASS__; /** @var $self Admin (for IDE) */

		Admin_Ajax::init();
		AdminNotices::init();
		( new WCAdminConnectPages() )->init();
		Analytics::init();

		add_action( 'current_screen', [ $self, 'includes' ] );
		add_action( 'admin_enqueue_scripts', [ $self, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $self, 'register_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $self, 'enqueue_scripts_and_styles' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $self, 'load_react_ui_scripts' ] );
		add_action( 'admin_menu', [ $self, 'admin_menu' ] );
		add_action( 'admin_footer', [ $self, 'replace_top_level_menu' ] );
		add_action( 'admin_head', [ $self, 'menu_highlight' ] );
		add_filter( 'submenu_file', [ $self, 'submenu_highlight' ], 10, 2 );
		add_filter( 'admin_menu', [ $self, 'alter_menu' ] );

		add_filter( 'woocommerce_screen_ids', [ $self, 'filter_woocommerce_screen_ids' ] );
		add_filter( 'woocommerce_display_admin_footer_text', [ $self, 'filter_woocommerce_display_footer_text' ] );
		add_action( 'current_screen', [ $self, 'remove_woocommerce_help_tab' ], 100 );

		add_filter( 'woocommerce_reports_screen_ids', [ $self, 'inject_woocommerce_reports_screen_ids' ] );
		add_filter( 'editor_stylesheets', [ $self, 'add_editor_styles' ] );

		add_action( 'current_screen', [ $self, 'screen_options' ] );
		add_filter( 'set-screen-option', [ $self, 'handle_save_screen_option' ], 10, 3 );

		if ( aw_request( 'action' ) === 'automatewoo-settings' ) {
			add_action( 'wp_loaded', [ $self, 'save_settings' ] );
		}

		if ( aw_request( 'page' ) === 'automatewoo-preview' && aw_request( 'action' ) === 'loading' ) {
			add_action( 'admin_init', [ $self, 'cache_preview_loader' ] );
		}

		if ( aw_request( 'page' ) === 'automatewoo-preview' ) {
			add_action( 'admin_notices', [ $self, 'remove_admin_notices' ], 0 );
		}

		$registry = BlocksPackage::container()->get( AssetDataRegistry::class );
		( new AssetData( $registry ) )->add_data();
	}

	/**
	 * Initialize classes based on current screen.
	 */
	public static function includes() {

		switch ( self::get_screen_id() ) {
			case 'aw_workflow':
				new Admin_Workflow_Edit();
				break;

			case 'edit-aw_workflow':
				new Admin_Workflow_List();
				break;

			case 'edit-shop_coupon':
				new \AW_Admin_Coupons_List();
				break;
		}
	}

	/**
	 * Add screen options based on current screen.
	 */
	public static function screen_options() {
		$screen_id = self::get_screen_id();

		switch ( $screen_id ) {
			case 'opt-ins':
				$screen_id = 'optins'; // Screen option with dash is not supported.
			case 'logs':
			case 'carts':
			case 'guests':
			case 'queue':
				add_screen_option(
					'per_page',
					[
						'option'  => "automatewoo_{$screen_id}_per_page",
						'default' => 20,
					]
				);
				break;
		}
	}


	/**
	 * Handle saving screen option.
	 *
	 * @param bool   $keep
	 * @param string $option
	 * @param int    $value
	 *
	 * @return bool|int
	 */
	public static function handle_save_screen_option( $keep, $option, $value ) {
		$options = [
			'automatewoo_logs_per_page',
			'automatewoo_carts_per_page',
			'automatewoo_queue_per_page',
			'automatewoo_guests_per_page',
			'automatewoo_optins_per_page',
		];

		if ( in_array( $option, $options, true ) ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Add menu entries.
	 */
	public static function admin_menu() {
		$sub_menu = [];
		$position = '55.6324'; // fix for rare position clash bug
		$icon     = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNTEyIDUxMiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGc+PHBvbHlnb24gZmlsbD0iIzlFQTNBOCIgcG9pbnRzPSIxMTcuNiwyNzIuNyAxNjYuOSwyNzIuNyAxNDIuMywyMDUuNCAiLz48cGF0aCBmaWxsPSIjOUVBM0E4IiBkPSJNNDY0LDU0LjRINDhDMjUuMSw1NC40LDYuNCw3My4xLDYuNCw5NnYzMjBjMCwyMi45LDE4LjcsNDEuNiw0MS42LDQxLjZoNDE2YzIyLjksMCw0MS42LTE4LjcsNDEuNi00MS42Vjk2QzUwNS42LDczLjEsNDg2LjksNTQuNCw0NjQsNTQuNHogTTE5My4zLDM0NS4xbC0xNC45LTQwLjdIMTA2bC0xNC44LDQwLjdINTQuNkwxMjMsMTY2LjloNDAuMkwyMzEsMzQ1LjFIMTkzLjN6IE00MDguMSwzNDUuMWgtMzUuN2wtNDAuNy0xMjYuOUwyOTEsMzQ1LjFoLTM2LjJsLTQ5LjUtMTc4LjJIMjQybDMyLjcsMTIyLjZsMzkuNS0xMjIuNmgzNS45TDM4OS45LDI5MEw0MjIsMTY2LjloMzUuNEw0MDguMSwzNDUuMXoiLz48L2c+PC9zdmc+';

		add_menu_page(
			__( 'AutomateWoo', 'automatewoo' ),
			__( 'AutomateWoo', 'automatewoo' ),
			'manage_woocommerce',
			'automatewoo',
			[ 'AutomateWoo\Admin', 'load_controller' ],
			$icon,
			$position
		);

		$sub_menu['dashboard'] = [
			'title'    => __( 'Dashboard', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		// Workflows menu group
		$sub_menu['workflows'] = [
			'title' => __( 'Workflows', 'automatewoo' ),
			'slug'  => 'edit.php?post_type=aw_workflow',
		];

		$sub_menu['logs']  = [
			'title'    => __( 'Logs', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];
		$sub_menu['queue'] = [
			'title'    => __( 'Queue', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		if ( Options::abandoned_cart_enabled() ) {
			$sub_menu['carts'] = [
				'title'    => __( 'Carts', 'automatewoo' ),
				'function' => [ __CLASS__, 'load_controller' ],
			];
		}

		$sub_menu['guests'] = [
			'title'    => __( 'Guests', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		$sub_menu['opt-ins'] = [
			'title'    => Options::optin_enabled() ? __( 'Opt-ins', 'automatewoo' ) : __( 'Opt-outs', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		$sub_menu['reports'] = [
			'title'    => __( 'Reports', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
			'enabled'  => ! HPOS_Helper::is_HPOS_enabled(),
		];

		$sub_menu['tools'] = [
			'title'    => __( 'Tools', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		$sub_menu['settings'] = [
			'title'    => __( 'Settings', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		$sub_menu['preview'] = [
			'title'    => __( 'Preview', 'automatewoo' ),
			'function' => [ __CLASS__, 'load_controller' ],
		];

		$sub_menu['data-upgrade'] = [
			'title'    => __( 'AutomateWoo Data Update', 'automatewoo' ),
			'function' => [ __CLASS__, 'page_data_upgrade' ],
		];

		foreach ( $sub_menu as $key => $item ) {
			if ( empty( $item['function'] ) ) {
				$item['function'] = '';
			}
			if ( empty( $item['capability'] ) ) {
				$item['capability'] = 'manage_woocommerce';
			}
			if ( empty( $item['slug'] ) ) {
				$item['slug'] = 'automatewoo-' . $key;
			}
			if ( empty( $item['page_title'] ) ) {
				$item['page_title'] = $item['title'];
			}

			$is_enabled = $item['enabled'] ?? true;

			add_submenu_page( $is_enabled ? 'automatewoo' : 'automatewoo_disabled', $item['page_title'], $item['title'], $item['capability'], $item['slug'], $item['function'] );

			if ( $key === 'workflows' ) {
				do_action( 'automatewoo/admin/submenu_pages', 'automatewoo' );
			}
		}

		if ( WC()->is_wc_admin_active() ) {
			wc_admin_register_page(
				[
					'id'     => 'automatewoo-manual-workflow-runner',
					'title'  => __( 'Manual Workflow Runner', 'automatewoo' ),
					'parent' => 'automatewoo',
					'path'   => '/automatewoo/manual-workflow-runner',
				]
			);
		}
	}

	/**
	 * This function alters some items in AutomateWoo submenu
	 * This is because we need to add .hide-if-js classname in the submenu for hide them in Calypso for wp.com
	 * The classes are usually loaded in position 4 in the submenu.
	 *
	 * @see Menu::migrate_menu_items for a reference of how WC Admin loads this class. (https://github.com/woocommerce/woocommerce/blob/47e4d918c91ea980c41f4567e823256acbac4317/plugins/woocommerce/src/Admin/Features/Navigation/Menu.php#L636)
	 * @param array $menu Menu items
	 * @return array Menu items
	 */
	public static function alter_menu( $menu ) {
		global $submenu;

		if ( isset( $submenu['automatewoo'] ) ) {
			foreach ( $submenu['automatewoo'] as $index => $submenu_item ) {
				if (
					$submenu_item[0] === 'AutomateWoo' ||
					$submenu_item[0] === 'AutomateWoo Data Update' ||
					$submenu_item[0] === 'Manual Workflow Runner' ||
					$submenu_item[0] === 'Preview'
				) {
					if ( isset( $submenu_item[4] ) ) {
						$submenu_item[4] .= ' hide-if-js'; // if there are some classes already, concat the new one
					} else {
						$submenu_item[] = 'hide-if-js'; // Otherwise, add the new class
					}

					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$submenu['automatewoo'][ $index ] = $submenu_item;
				}
			}
		}

		return $menu;
	}

	/**
	 * Highlight the correct top level admin menu item
	 */
	public static function menu_highlight() {
		global $parent_file, $post_type;

		switch ( $post_type ) {
			case 'aw_workflow':
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$parent_file = 'automatewoo';
				break;
		}
	}

	/**
	 * Filter to highlight submenu that cannot be handled well in WordPress core.
	 *
	 * @param string|null $submenu_file
	 * @param string      $parent_file
	 *
	 * @return string|null
	 */
	public static function submenu_highlight( $submenu_file, $parent_file ) {
		// To highlight the "Workflows" submenu for the "Add New Workflow" page,
		// it makes the `$submenu_file` match the "Workflows" submenu's slug registered
		// in the `self::admin_menu`.
		//
		// Ref:
		// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/wp-admin/menu-header.php#L40-L48
		// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/wp-admin/menu-header.php#L223-L227
		if (
			$parent_file === 'automatewoo' &&
			PageController::get_instance()->get_current_screen_id() === 'aw_workflow-add'
		) {
			return 'edit.php?post_type=aw_workflow';
		}

		return $submenu_file;
	}

	/**
	 * Register admin scripts.
	 */
	public static function register_scripts() {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$url    = AW()->admin_assets_url( '/js' );
			$suffix = '';
		} else {
			$url    = AW()->admin_assets_url( '/js/min' );
			$suffix = '.min';
		}

		wp_register_script( 'js-cookie', WC()->plugin_url() . "/assets/js/js-cookie/js.cookie{$suffix}.js", [], '2.1.4', true );

		wp_register_script( 'automatewoo', "{$url}/automatewoo{$suffix}.js", [ 'jquery', 'jquery-ui-datepicker', 'jquery-tiptip', 'backbone', 'underscore' ], AW()->version, false );
		wp_register_script( 'automatewoo-validate', "{$url}/validate{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-tracks', "{$url}/tracks{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-workflows', "{$url}/workflows{$suffix}.js", [ 'automatewoo', 'automatewoo-validate', 'automatewoo-modal', 'automatewoo-tracks', 'wp-util' ], AW()->version, false );
		wp_register_script( 'automatewoo-variables', "{$url}/variables{$suffix}.js", [ 'automatewoo-modal', 'clipboard' ], AW()->version, false );
		wp_register_script( 'automatewoo-tools', "{$url}/tools{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-sms-test', "{$url}/sms-test{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-modal', "{$url}/modal{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-rules', "{$url}/rules{$suffix}.js", [ 'automatewoo', 'automatewoo-workflows' ], AW()->version, false );
		wp_register_script( 'automatewoo-dashboard', "{$url}/dashboard{$suffix}.js", [ 'automatewoo', 'automatewoo-modal', 'jquery-masonry', 'flot', 'flot-resize', 'flot-time', 'flot-pie', 'flot-stack' ], AW()->version, false );
		wp_register_script( 'automatewoo-preview', "{$url}/preview{$suffix}.js", [ 'automatewoo' ], AW()->version, false );
		wp_register_script( 'automatewoo-settings', "{$url}/settings{$suffix}.js", [ 'automatewoo' ], AW()->version, false );

		global $wp_locale;

		wp_localize_script( 'automatewoo-dashboard', 'automatewooDashboardLocalizeScript', [] );

		wp_localize_script(
			'automatewoo-validate',
			'automatewooValidateLocalizedErrorMessages',
			[
				'noVariablesSupport' => __( 'This field does not support variables.', 'automatewoo' ),
				/* translators: Variable name. */
				'invalidDataType'    => __( "Variable '%s' is not available with the selected trigger. Please only use variables listed in the the variables box.", 'automatewoo' ),
				/* translators: Variable name. */
				'invalidVariable'    => __( "Variable '%s' is not valid. Please only use variables listed in the variables box.", 'automatewoo' ),
				'noTrigger'          => __( 'Choose a trigger before selecting a variable.', 'automatewoo' ),
			]
		);

		$settings = [
			'url'    => [
				'admin' => admin_url(),
				'ajax'  => admin_url( 'admin-ajax.php' ),
			],
			'locale' => [
				'month_abbrev'                => array_values( $wp_locale->month_abbrev ),
				'currency_symbol'             => get_woocommerce_currency_symbol(),
				'currency_decimal_separator'  => wc_get_price_decimal_separator(),
				'currency_thousand_separator' => wc_get_price_thousand_separator(),
				'currency_position'           => get_option( 'woocommerce_currency_pos' ),
			],
			'nonces' => [
				'remove_notice'                  => wp_create_nonce( 'aw-remove-notice' ),
				'aw_dismiss_system_error_notice' => wp_create_nonce( 'aw_dismiss_system_error_notice' ),
				'aw_toggle_workflow_status'      => wp_create_nonce( 'aw_toggle_workflow_status' ),
			],
		];

		wp_localize_script(
			'automatewoo',
			'automatewooLocalizeScript',
			apply_filters( 'automatewoo/admin/js_settings', $settings )
		);

		wp_localize_script(
			'automatewoo-preview',
			'automatewooPreviewLocalizeScript',
			[
				'nonce' => wp_create_nonce( 'aw_send_test_email' ),
			]
		);

		wp_localize_script(
			'automatewoo-sms-test',
			'automatewooSmsTestLocalizeScript',
			[
				'nonce' => wp_create_nonce( 'aw_test_sms' ),
			]
		);
	}

	/**
	 * Register admin styles.
	 */
	public static function register_styles() {
		wp_register_style(
			'automatewoo-main',
			AW()->admin_assets_url( '/css/aw-main.css' ),
			[],
			AW()->version
		);
		wp_register_style( 'automatewoo-preview', AW()->admin_assets_url( '/css/preview.css' ), [], AW()->version );
	}

	/**
	 * Enqueue scripts based on screen id
	 */
	public static function enqueue_scripts_and_styles() {
		$screen_id    = self::get_current_screen_id();
		$is_aw_screen = self::is_automatewoo_screen();

		// Load WC Admin styles for AW pages before our own styles
		if ( defined( 'WC_ADMIN_APP' ) && $is_aw_screen ) {
			wp_enqueue_style( WC_ADMIN_APP );
		}

		wp_enqueue_script( 'automatewoo' );
		wp_enqueue_style( 'automatewoo-main' );

		if ( self::should_react_ui_be_loaded() ) {
			wp_enqueue_style(
				'automatewoo-webpack',
				AW()->admin_assets_url( '/build/index.css' ),
				[ 'wc-admin-app' ],
				AW()->version
			);
		}

		if ( $is_aw_screen ) {
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_script( 'jquery-tiptip' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'js-cookie' );

			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'jquery-ui-style' );
		}

		if ( $screen_id === 'automatewoo_page_automatewoo-preview' ) {
			wp_enqueue_script( 'automatewoo-preview' );
			wp_enqueue_style( 'automatewoo-preview' );
		}
	}

	/**
	 * Should the react UI load?
	 *
	 * If the current page is an AW php page or a WC Admin JS-powered page, then yes.
	 *
	 * @since 5.1.2
	 *
	 * @return bool
	 */
	protected static function should_react_ui_be_loaded() {
		return WC()->is_wc_admin_active() && ( self::is_automatewoo_screen() || wc_admin_is_registered_page() );
	}

	/**
	 * Load react powered admin JS.
	 *
	 * @since 5.0.0
	 */
	public static function load_react_ui_scripts() {
		if ( ! self::should_react_ui_be_loaded() ) {
			return;
		}

		$asset_file_path = AW()->admin_path() . '/assets/build/index.asset.php';

		if ( ! file_exists( $asset_file_path ) ) {
			return;
		}

		$asset_file   = (array) include $asset_file_path;
		$dependencies = isset( $asset_file['dependencies'] ) ? $asset_file['dependencies'] : [];
		// Depend on main admin script for plugin settings
		$dependencies = array_merge( $dependencies, [ 'jquery', 'automatewoo', 'wc-settings' ] );
		$version      = isset( $asset_file['version'] ) ? $asset_file['version'] : false;
		$handle       = 'automatewoo-react-ui';

		wp_enqueue_script(
			$handle,
			AW()->admin_assets_url() . '/build/index.js',
			$dependencies,
			$version,
			true
		);
		wp_set_script_translations( $handle, 'automatewoo', AW()->path( '/languages' ) );
	}

	/**
	 * Return filtered screen ids.
	 *
	 * @return array List of screen ids.
	 */
	public static function screen_ids() {
		$ids    = [];
		$prefix = 'automatewoo_page_automatewoo';

		$ids[] = "$prefix-dashboard";
		$ids[] = "$prefix-logs";
		$ids[] = "$prefix-reports";
		$ids[] = "$prefix-settings";
		$ids[] = "$prefix-tools";
		$ids[] = "$prefix-carts";
		$ids[] = "$prefix-queue";
		$ids[] = "$prefix-guests";
		$ids[] = "$prefix-opt-ins";
		$ids[] = "$prefix-preview";
		$ids[] = 'aw_workflow';
		$ids[] = 'edit-aw_workflow';

		return apply_filters( 'automatewoo/admin/screen_ids', $ids );
	}


	/**
	 * Add AW screens to the woocommerce screen IDs list.
	 * Important for admin script loading.
	 *
	 * @since 4.4.2
	 *
	 * @param array $screen_ids
	 *
	 * @return array
	 */
	public static function filter_woocommerce_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, self::screen_ids() );
		return $screen_ids;
	}


	/**
	 * Hide the WC footer message on AW screens.
	 *
	 * @since 4.4.2
	 *
	 * @param bool $display
	 *
	 * @return bool
	 */
	public static function filter_woocommerce_display_footer_text( $display ) {
		if ( self::is_automatewoo_screen() ) {
			$display = false;
		}

		return $display;
	}

	/**
	 * Remove the WC help tab on AW screens
	 *
	 * @since 4.4.2
	 */
	public static function remove_woocommerce_help_tab() {
		if ( self::is_automatewoo_screen() ) {
			$screen = get_current_screen();
			$screen->remove_help_tabs();
		}
	}

	/**
	 * Dynamic replace top level menu
	 */
	public static function replace_top_level_menu() {
		?>
		<script type="text/javascript">
			jQuery('#adminmenu').find('a.toplevel_page_automatewoo').attr('href', '<?php echo esc_url( self::page_url( 'dashboard' ) ); ?>');
		</script>
		<?php
	}

	/**
	 * Return a page URL based on page type.
	 *
	 * @param string   $page
	 * @param bool|int $id
	 * @return false|string
	 */
	public static function page_url( $page, $id = false ) {

		switch ( $page ) {

			case 'dashboard':
				return admin_url( 'admin.php?page=automatewoo-dashboard' );

			case 'workflows':
				return admin_url( 'edit.php?post_type=aw_workflow' );

			case 'workflow-presets':
				return admin_url( 'edit.php?post_type=aw_workflow#presets' );

			case 'settings':
				return admin_url( 'admin.php?page=automatewoo-settings' );

			case 'settings-bitly':
				return admin_url( 'admin.php?page=automatewoo-settings&tab=bitly' );

			case 'logs':
				return admin_url( 'admin.php?page=automatewoo-logs' );

			case 'queue':
				return admin_url( 'admin.php?page=automatewoo-queue' );

			case 'guests':
				return admin_url( 'admin.php?page=automatewoo-guests' );

			case 'guest':
				return admin_url( "admin.php?page=automatewoo-guests&action=view&guest_id=$id" );

			case 'email-tracking':
				return admin_url( 'admin.php?page=automatewoo-reports&tab=email-tracking' );

			case 'carts':
				return admin_url( 'admin.php?page=automatewoo-carts' );

			case 'opt-ins':
				return admin_url( 'admin.php?page=automatewoo-opt-ins' );

			case 'conversions':
				return admin_url( 'admin.php?page=automatewoo-reports&tab=conversions' );

			case 'workflows-report':
				return admin_url( 'admin.php?page=automatewoo-reports&tab=runs-by-date' );

			case 'tools':
				return admin_url( 'admin.php?page=automatewoo-tools' );

			case 'tool-optin-importer':
				return admin_url( 'admin.php?page=automatewoo-tools&action=view&tool_id=optin_importer' );

			case 'tool-optout-importer':
				return admin_url( 'admin.php?page=automatewoo-tools&action=view&tool_id=optout_importer' );

			case 'status':
				return admin_url( 'admin.php?page=automatewoo-settings&tab=status' );

			case 'manual-workflow-runner':
				$url = admin_url( 'admin.php?page=wc-admin&path=/automatewoo/manual-workflow-runner' );
				if ( $id ) {
					// SEMGREP WARNING EXPLANATION
					// URL is escaped. However, Semgrep only considers esc_url as valid.
					return esc_url_raw( add_query_arg( 'workflowId', $id, $url ) );
				}
				return $url;

			case 'analytics':
				$url = admin_url( 'admin.php?page=wc-admin&path=/analytics/automatewoo-' . $id );
				return $url;

			case 'documentation':
				return 'https://woocommerce.com/document/automatewoo/?utm_source=wordpress&utm_medium=all-plugins-page&utm_campaign=doc-link&utm_content=automatewoo';
		}

		return false;
	}

	/**
	 * Data upgrade page.
	 */
	public static function page_data_upgrade() {
		self::get_view( 'page-data-upgrade' );
	}

	/**
	 * Get a view.
	 *
	 * Both calling extract and include are based off of the methods used in `wc_get_template`.
	 *
	 * @param string $view
	 * @param array  $imported_variables
	 * @param mixed  $path
	 */
	public static function get_view( $view, $imported_variables = [], $path = false ) {

		if ( $imported_variables && is_array( $imported_variables ) ) {
			extract( $imported_variables ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		if ( ! $path ) {
			$path = AW()->admin_path( '/views/' );
		}

		include "{$path}{$view}.php"; // nosemgrep No user input reached. This is for loading our views.
	}

	/**
	 * Load controller.
	 */
	public static function load_controller() {
		$screen_id = self::get_screen_id();

		if ( ! $screen_id ) {
			return;
		}

		if ( $screen_id === 'toplevel_page_automatewoo' ) {
			$screen_id = 'dashboard';
		}

		$controller = Admin\Controllers::get( $screen_id );
		if ( $controller ) {
			$controller->handle();
		}
	}

	/**
	 * Get the current screen ID.
	 *
	 * @return string|bool
	 */
	public static function get_screen_id() {
		$screen_id = self::get_current_screen_id();

		// Replace hidden screen ID's without AutomateWoo as parent.
		$screen_id = str_replace( 'admin_page_automatewoo-', '', $screen_id );

		if ( ! $screen_id ) {
			return false;
		}

		$base_screen = sanitize_title( __( 'AutomateWoo', 'automatewoo' ) ); // required if plugin name was translated

		return str_replace( $base_screen . '_page_automatewoo-', '', $screen_id );
	}

	/**
	 * Save settings on wp_loaded
	 */
	public static function save_settings() {
		$controller = Admin\Controllers::get( 'settings' );
		if ( $controller ) {
			$controller->save();
		}
	}

	/**
	 * Check if we are on a specific page.
	 *
	 * @param string $page
	 * @return bool
	 */
	public static function is_page( $page ) {

		$current_page = Clean::string( aw_request( 'page' ) );
		$current_tab  = Clean::string( aw_request( 'tab' ) );

		switch ( $page ) {
			case 'dashboard':
				return $current_page === 'automatewoo-dashboard';
			case 'settings':
				return $current_page === 'automatewoo-settings';
			case 'reports':
				return $current_page === 'automatewoo-reports';
			case 'status':
				return $current_page === 'automatewoo-settings' && $current_tab === 'status';
		}

		return false;
	}

	/**
	 * Display an admin notice.
	 *
	 * @param string $type         (warning,error,success)
	 * @param string $strong       highlighted notice content (text or html)
	 * @param string $more         notice content (text or html)
	 * @param string $classes      extra classes to add to the notice (eg., is-dismissible)
	 * @param string $button_text  text to display on the primary button (not displayed if empty)
	 * @param string $button_link  link for the button (not displayed if empty)
	 * @param string $button_class extra classes to add to the button
	 */
	public static function notice( $type, $strong, $more = '', $classes = '', $button_text = '', $button_link = '', $button_class = '' ) {
		self::get_view(
			'simple-notice',
			[
				'type'         => $type,
				'class'        => $classes,
				'strong'       => $strong,
				'message'      => $more,
				'button_text'  => $button_text,
				'button_link'  => $button_link,
				'button_class' => $button_class,
			]
		);
	}

	/**
	 * Add WooCommerce Reports screen IDs.
	 *
	 * @param array $ids
	 * @return array
	 */
	public static function inject_woocommerce_reports_screen_ids( $ids ) {
		$ids[] = 'automatewoo_page_automatewoo-reports';
		return $ids;
	}

	/**
	 * Add editor styles.
	 *
	 * @param array $stylesheets
	 * @return array
	 */
	public static function add_editor_styles( $stylesheets ) {
		$stylesheets[] = AW()->admin_assets_url( '/css/editor.css' );
		return $stylesheets;
	}

	/**
	 * Add a meta box.
	 *
	 * @param string   $id
	 * @param string   $title
	 * @param callable $callback
	 * @param null     $screen
	 * @param string   $context
	 * @param string   $priority
	 * @param null     $callback_args
	 */
	public static function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		$id = 'aw_' . $id;

		add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );

		add_filter( "postbox_classes_{$screen}_{$id}", [ __CLASS__, 'inject_postbox_class' ] );
	}

	/**
	 * Add postbox class.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public static function inject_postbox_class( $classes ) {
		$classes[] = 'automatewoo-metabox';
		$classes[] = 'no-drag';
		return $classes;
	}

	/**
	 * Add hidden form inputs.
	 *
	 * @param array $vars
	 */
	public static function get_hidden_form_inputs_from_query( $vars ) {
		foreach ( $vars as $var ) {
			// Adding a nonce is handled by the calling functions.
			// Validating of sanatized input is done when the values are read (we are copying the values here).
			// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
			if ( empty( $_GET[ $var ] ) ) {
				continue;
			}

			echo '<input type="hidden" name="' . esc_attr( $var ) . '" value="' . esc_attr( $_GET[ $var ] ) . '">';
			// phpcs:enable
		}
	}


	/**
	 * Display a help tip.
	 *
	 * @param string $tip        The tip to display. Not expected to be escaped.
	 * @param bool   $pull_right Whether the tip should include the automatewoo-help-tip--right class
	 */
	public static function help_tip( $tip, $pull_right = true ) {
		$tip = wc_sanitize_tooltip( $tip );
		if ( empty( $tip ) ) {
			return;
		}

		$classes = array_filter(
			[
				'automatewoo-help-tip',
				'woocommerce-help-tip',
				$pull_right ? 'automatewoo-help-tip--right' : '',
			]
		);

		printf( '<span class="%1$s" data-tip="%2$s"></span>', esc_attr( join( ' ', $classes ) ), esc_attr( $tip ) );
	}


	/**
	 * Return an AutomateWoo badge.
	 *
	 * @param string $type
	 * @param string $dashicon
	 * @param bool   $tip
	 * @return string
	 */
	public static function badge( $type, $dashicon, $tip = false ) {
		$html  = '<span class="automatewoo-badge automatewoo-badge--' . $type . ' automatewoo-tiptip" data-tip="' . esc_attr( $tip ) . '">';
		$html .= '<span class="dashicons dashicons-' . $dashicon . '"></span>';
		$html .= '</span>';
		return $html;
	}


	/**
	 * Get a help link.
	 *
	 * @param string $url
	 * @param bool   $pull_right
	 * @return string
	 */
	public static function help_link( $url, $pull_right = true ) {
		return '<a href="' . esc_url( $url ) . '" class="automatewoo-help-link ' . ( $pull_right ? 'automatewoo-help-link--right' : '' ) . '" target="_blank"></a>';
	}


	/**
	 * Get documentation link.
	 *
	 * @param string      $page
	 * @param string|bool $utm_source
	 * @param string|bool $utm_campaign
	 * @return string
	 */
	public static function get_docs_link( $page = '', $utm_source = false, $utm_campaign = false ) {
		return esc_url_raw( self::get_website_link( "docs/$page", $utm_source, $utm_campaign ) );
	}

	/**
	 * Get a website link.
	 *
	 * @param string      $page
	 * @param string|bool $utm_source
	 * @param string|bool $utm_campaign
	 * @return string
	 */
	public static function get_website_link( $page = '', $utm_source = false, $utm_campaign = false ) {
		$url = 'https://automatewoo.com/' . ( $page ? trailingslashit( $page ) : '' );

		if ( $utm_source ) {
			// SEMGREP WARNING EXPLANATION
			// This is escaped in the consumer function.
			$url = add_query_arg(
				[
					'utm_source'   => $utm_source,
					'utm_medium'   => 'plugin',
					'utm_campaign' => $utm_campaign ? $utm_campaign : 'plugin-links',
				],
				$url
			);
		}

		return $url;
	}

	/**
	 * Get WooCommerce.com marketplace product link.
	 *
	 * @since 3.7.0
	 *
	 * @param string $product
	 *
	 * @return string
	 */
	public static function get_marketplace_product_link( $product = 'automatewoo' ) {
		return "https://woocommerce.com/products/$product/";
	}

	/**
	 * Output loader
	 */
	public static function cache_preview_loader() {
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		@header_remove( 'Cache-Control' );
		@header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + DAY_IN_SECONDS ) . ' GMT' );
		// phpcs:enable
	}


	/**
	 * Create a new page.
	 *
	 * @param string $slug
	 * @param string $page_title
	 * @param string $page_content
	 * @param string $option
	 * @return int|bool Page ID
	 */
	public static function create_page( $slug, $page_title, $page_content, $option ) {

		if ( get_option( $option ) ) {
			return false; // page is already defined in settings
		}

		$page_data = [
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'comment_status' => 'closed',
		];

		$page_id = wp_insert_post( $page_data );
		update_option( $option, $page_id, false );

		return $page_id;
	}

	/**
	 * Return the current WP screen ID.
	 *
	 * @since 4.4.2
	 *
	 * @return bool|string
	 */
	public static function get_current_screen_id() {
		$screen = get_current_screen();
		return $screen ? $screen->id : false;
	}

	/**
	 * Is the current WP screen an AutomateWoo screen?
	 *
	 * @since 4.7.0
	 * @return bool
	 */
	public static function is_automatewoo_screen() {
		return in_array( self::get_current_screen_id(), self::screen_ids(), true );
	}

	/**
	 * Unhook all admin notices.
	 *
	 * @since 4.4.0
	 */
	public static function remove_admin_notices() {
		remove_all_actions( 'admin_notices' );
	}

	/**
	 * Get marketplace subscriptions tab URL.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public static function get_marketplace_subscriptions_tab_url() {
		return add_query_arg(
			[
				'page'    => 'wc-addons',
				'section' => 'helper',
			],
			admin_url( 'admin.php' )
		);
	}
}

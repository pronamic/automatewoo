<?php

namespace AutomateWoo;

/**
 * Class to manage admin notices.
 *
 * @since   4.7.0
 * @package AutomateWoo
 */
class AdminNotices {

	/**
	 * Store of admin notices.
	 *
	 * @var array
	 */
	private static $notices;

	/**
	 * Whether notices have been changed and need to be saved.
	 *
	 * @var bool
	 */
	private static $has_changes = false;

	/**
	 * Define whether AdminNotices::init() has run.
	 *
	 * @var bool
	 */
	private static $did_init = false;

	/**
	 * Init admin notices.
	 */
	public static function init() {
		if ( self::$did_init ) {
			return;
		}

		self::$did_init = true;
		add_action( 'shutdown', [ __CLASS__, 'save_notices' ] );

		if ( Permissions::can_manage() ) {
			add_action( 'wp_ajax_automatewoo_remove_notice', [ __CLASS__, 'handle_ajax_remove_notice' ] );
			add_action( 'admin_notices', [ __CLASS__, 'output_notices' ] );
		}
	}

	/**
	 * Get current admin notices.
	 *
	 * @return array
	 */
	public static function get_notices() {
		if ( ! isset( self::$notices ) ) {
			self::$notices = get_option( 'automatewoo_admin_notices', [] );
		}
		return self::$notices;
	}

	/**
	 * Add a notice.
	 *
	 * @param string $name
	 */
	public static function add_notice( $name ) {
		self::init();

		self::$notices     = array_unique( array_merge( self::get_notices(), [ $name ] ) );
		self::$has_changes = true;
	}

	/**
	 * Remove a notice.
	 *
	 * @param string $name
	 */
	public static function remove_notice( $name ) {
		self::init();

		self::$notices     = array_diff( self::get_notices(), [ $name ] );
		self::$has_changes = true;
	}

	/**
	 * Save notices to database.
	 */
	public static function save_notices() {
		if ( self::$has_changes ) {
			update_option( 'automatewoo_admin_notices', self::get_notices() );
		}
	}

	/**
	 * Output admin notices.
	 */
	public static function output_notices() {
		// Only show notices on AW screens
		if ( ! Admin::is_automatewoo_screen() ) {
			return;
		}

		$notices = self::get_notices();

		foreach ( $notices as $notice ) {
			$method_name = "output_{$notice}_notice";
			if ( is_callable( [ __CLASS__, $method_name ] ) ) {
				call_user_func( [ __CLASS__, $method_name ] );
			} else {
				do_action( "automatewoo/admin_notice/{$notice}" );
			}
		}
	}

	/**
	 * Remove notice by ajax request.
	 */
	public static function handle_ajax_remove_notice() {
		if ( ! wp_verify_nonce( sanitize_key( aw_get_post_var( 'nonce' ) ), 'aw-remove-notice' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'automatewoo' ) );
		}

		$notice = Clean::string( aw_get_post_var( 'notice' ) );
		if ( $notice ) {
			self::remove_notice( $notice );
		}
		die;
	}

	/**
	 * Output the notice about upcoming minimum requirements changes.
	 *
	 * @since 4.9.5
	 */
	private static function output_requirements_changes_notice() {
		$notice_identifier = 'requirements_changes';

		// No upcoming requirements changes.
		if (
			version_compare( AUTOMATEWOO_MIN_WP_VER, AUTOMATEWOO_NOTICE_WP_VER, '=' )
			&& version_compare( AUTOMATEWOO_MIN_WC_VER, AUTOMATEWOO_NOTICE_WC_VER, '=' )
		) {
			self::remove_notice( $notice_identifier );
			return;
		}

		// Upcoming version is the same as or less than the current version.
		if ( version_compare( AUTOMATEWOO_VERSION, AUTOMATEWOO_NOTICE_AW_VER, '>=' ) ) {
			self::remove_notice( $notice_identifier );
			return;
		}

		global $wp_version;
		$warn_wp = version_compare( $wp_version, AUTOMATEWOO_NOTICE_WP_VER, '<' );
		$warn_wc = version_compare( WC()->version, AUTOMATEWOO_NOTICE_WC_VER, '<' );

		// Nothing is behind the new requirements.
		if ( ! $warn_wp && ! $warn_wc ) {
			self::remove_notice( $notice_identifier );
			return;
		}

		$aw_pretty_version = aw_prettify_version( AUTOMATEWOO_NOTICE_AW_VER );
		$wp_pretty_version = aw_prettify_version( AUTOMATEWOO_NOTICE_WP_VER );
		$wc_pretty_version = aw_prettify_version( AUTOMATEWOO_NOTICE_WC_VER );

		if ( $warn_wp && $warn_wc ) {
			$message = sprintf(
				/* translators: %1$s AutomateWoo version, %2$s WordPress version, %3$s WooCommerce version */
				__( 'AutomateWoo %1$s will require WordPress version %2$s or newer and WooCommerce version %3$s or newer.', 'automatewoo' ),
				$aw_pretty_version,
				$wp_pretty_version,
				$wc_pretty_version
			);
		} elseif ( $warn_wp ) {
			$message = sprintf(
				/* translators: %1$s AutomateWoo version, %2$s WordPress version */
				__( 'AutomateWoo %1$s will require WordPress version %2$s or newer.', 'automatewoo' ),
				$aw_pretty_version,
				$wp_pretty_version
			);
		} elseif ( $warn_wc ) {
			$message = sprintf(
				/* translators: %1$s AutomateWoo version, %2$s WooCommerce version */
				__( 'AutomateWoo %1$s will require WooCommerce version %2$s or newer.', 'automatewoo' ),
				$aw_pretty_version,
				$wc_pretty_version
			);
		}
		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => $notice_identifier,
				'type'              => 'warning',
				'class'             => 'is-dismissible',
				'strong'            => __( 'Update required soon:', 'automatewoo' ),
				'message'           => $message,
			]
		);
	}
}

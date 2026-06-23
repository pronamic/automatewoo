<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\Admin;
use AutomateWoo\AdminNotices;
use AutomateWoo\Options;
use AutomateWoo\Permissions;

defined( 'ABSPATH' ) || exit;

/**
 * Class NonProductionEnvironment
 *
 * Displays an admin notice when AutomateWoo is running on a non-production
 * environment, where workflows are automatically prevented from running.
 * Provides an override button so the merchant can explicitly opt in.
 *
 * @since 6.5.0
 */
class NonProductionEnvironment extends AbstractAdminNotice {

	/**
	 * Get the unique notice ID.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'non_production_environment';
	}

	/**
	 * Init the notice, add hooks.
	 */
	public function init() {
		if ( ! self::is_non_production() ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'handle_override_action' ] );
		add_action( 'admin_init', [ $this, 'handle_reenable_lock_action' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_badge' ], 99 );

		if ( Options::non_production_workflow_override() ) {
			add_action( 'automatewoo/admin_notice/non_production_override_active', [ $this, 'output_override_active_notice' ] );
			if ( ! in_array( 'non_production_override_active', AdminNotices::get_notices(), true ) ) {
				AdminNotices::add_notice( 'non_production_override_active' );
			}
			return;
		}

		parent::init();
		if ( ! in_array( $this->get_id(), AdminNotices::get_notices(), true ) ) {
			$this->add_notice();
		}
	}

	/**
	 * Output/render the notice HTML.
	 */
	public function output() {
		$environment_type = self::get_environment_type();
		$button_url       = wp_nonce_url(
			add_query_arg( 'automatewoo_override_environment_lock', '1' ),
			'automatewoo_environment_override'
		);

		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => $this->get_id(),
				'type'              => 'info',
				'class'             => '',
				'strong'            => __( 'AutomateWoo workflows are paused.', 'automatewoo' ),
				'message'           => sprintf(
					/* translators: 1: the detected environment type, 2: opening <a> tag, 3: closing </a> tag */
					__( 'This site is running in a <strong>%1$s</strong> environment. Workflows have been automatically paused to prevent unintended emails, SMS messages, and other actions from being sent to real customers. %2$sEnable workflows anyway%3$s', 'automatewoo' ),
					esc_html( $environment_type ),
					'<a href="' . esc_url( $button_url ) . '" class="button" style="margin-left: 8px; vertical-align: baseline;">',
					'</a>'
				),
			]
		);
	}

	/**
	 * Output a softer notice when the merchant has overridden the environment lock.
	 */
	public function output_override_active_notice() {
		$button_url = wp_nonce_url(
			add_query_arg( 'automatewoo_reenable_environment_lock', '1' ),
			'automatewoo_environment_reenable_lock'
		);

		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => 'non_production_override_active',
				'type'              => 'warning',
				'class'             => '',
				'strong'            => __( 'AutomateWoo workflows are running on a non-production environment.', 'automatewoo' ),
				'message'           => sprintf(
					/* translators: 1: the detected environment type, 2: opening <a> tag, 3: closing </a> tag */
					__( 'This site is running in a <strong>%1$s</strong> environment. Workflows have been manually enabled and will send real emails, SMS, and other actions. %2$sPause workflows%3$s', 'automatewoo' ),
					esc_html( self::get_environment_type() ),
					'<a href="' . esc_url( $button_url ) . '" class="button" style="margin-left: 8px; vertical-align: baseline;">',
					'</a>'
				),
			]
		);
	}

	/**
	 * Add a badge to the Workflows submenu indicating the site is in a non-production environment.
	 */
	public function add_menu_badge() {
		global $submenu;

		if ( empty( $submenu['automatewoo'] ) ) {
			return;
		}

		$environment_type = self::get_environment_type();

		foreach ( $submenu['automatewoo'] as &$item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=aw_workflow' === $item[2] ) {
				$item[0] .= sprintf(
					' <span class="awaiting-mod" style="background: #d63638;"><span>%s</span></span>',
					esc_html( $environment_type )
				);
				break;
			}
		}
	}

	/**
	 * Handle the override action when the merchant clicks "Enable workflows anyway".
	 */
	public function handle_override_action() {
		if ( ! isset( $_GET['automatewoo_override_environment_lock'] ) ) {
			return;
		}

		if ( ! Permissions::can_manage() ) {
			return;
		}

		check_admin_referer( 'automatewoo_environment_override' );

		update_option( 'automatewoo_non_production_workflow_override', 'yes' );
		AdminNotices::remove_notice( $this->get_id() );
		AdminNotices::add_notice( 'non_production_override_active' );

		wp_safe_redirect( remove_query_arg( [ 'automatewoo_override_environment_lock', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Handle the re-enable lock action when the merchant wants to pause workflows again.
	 */
	public function handle_reenable_lock_action() {
		if ( ! isset( $_GET['automatewoo_reenable_environment_lock'] ) ) {
			return;
		}

		if ( ! Permissions::can_manage() ) {
			return;
		}

		check_admin_referer( 'automatewoo_environment_reenable_lock' );

		delete_option( 'automatewoo_non_production_workflow_override' );
		AdminNotices::remove_notice( 'non_production_override_active' );
		$this->add_notice();

		wp_safe_redirect( remove_query_arg( [ 'automatewoo_reenable_environment_lock', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Check whether the current environment is non-production.
	 *
	 * @return bool
	 */
	public static function is_non_production(): bool {
		return 'production' !== self::get_environment_type();
	}

	/**
	 * Get the current environment type.
	 *
	 * @return string
	 */
	public static function get_environment_type(): string {
		/**
		 * Filter the current environment type.
		 *
		 * @since 6.5.0
		 *
		 * @param string $environment_type The detected WordPress environment type.
		 */
		return (string) apply_filters( 'automatewoo/workflow/environment_type', wp_get_environment_type() );
	}

	/**
	 * Determine whether workflows should be prevented from running due to environment.
	 *
	 * Returns true when the site is non-production and the merchant has NOT opted to override.
	 *
	 * @return bool
	 */
	public static function is_environment_locked(): bool {
		if ( ! self::is_non_production() ) {
			return false;
		}

		if ( Options::non_production_workflow_override() ) {
			return false;
		}

		/**
		 * Filter whether workflows are locked due to a non-production environment.
		 *
		 * @since 6.5.0
		 *
		 * @param bool $is_locked True if workflows should be prevented.
		 */
		return apply_filters( 'automatewoo/workflow/is_environment_locked', true );
	}
}

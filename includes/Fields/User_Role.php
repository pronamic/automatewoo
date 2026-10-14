<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class User_Role
 */
class User_Role extends Select {

	/** @var string */
	protected $name = 'user_type'; // legacy name

	/** @var string[] */
	public const PROTECTED_ROLES = [ 'administrator', 'shop_manager' ];

	/**
	 * Capabilities that make a role off limits for the Change Role action.
	 *
	 * @since 6.9.3
	 *
	 * @return string[]
	 */
	public static function get_protected_capabilities(): array {
		/**
		 * Filters the capabilities that make a role off limits for the Change Role action.
		 *
		 * @since 6.9.3
		 *
		 * @param string[] $capabilities
		 */
		return (array) apply_filters(
			'automatewoo/change_role/protected_capabilities',
			[
				'manage_options',
				'manage_woocommerce',
				'edit_users',
				'promote_users',
				'create_users',
				'delete_users',
				'remove_users',
				'list_users',
				'install_plugins',
				'activate_plugins',
				'edit_plugins',
				'delete_plugins',
				'update_plugins',
				'install_themes',
				'switch_themes',
				'edit_themes',
				'delete_themes',
				'update_themes',
				'edit_theme_options',
				'update_core',
				'edit_files',
				'unfiltered_html',
				'unfiltered_upload',
				'edit_shop_orders',
			]
		);
	}

	/**
	 * Whether a role is protected, is unknown, or grants a protected capability.
	 *
	 * @since 6.9.3
	 *
	 * @param string $role Role key.
	 * @return bool
	 */
	public static function is_protected_role( string $role ): bool {
		if ( in_array( $role, self::PROTECTED_ROLES, true ) ) {
			return true;
		}

		$wp_role = get_role( $role );
		if ( ! $wp_role ) {
			return true;
		}

		foreach ( self::get_protected_capabilities() as $capability ) {
			if ( $wp_role->has_cap( $capability ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param bool $allow_any If it is allowed to select "any" as value for this field.
	 * @param bool $allow_all_roles If protected roles should be shown as well. See self::is_protected_role().
	 */
	public function __construct( bool $allow_any = true, bool $allow_all_roles = true ) {
		parent::__construct();

		$this->set_title( __( 'User role', 'automatewoo' ) );

		if ( $allow_any ) {
			$this->set_placeholder( '[Any]' );
		}

		global $wp_roles;

		foreach ( $wp_roles->roles as $key => $role ) {
			if ( $allow_all_roles || ! self::is_protected_role( $key ) ) {
				$this->options[ $key ] = $role['name'];
			}
		}
	}

	/**
	 * Keep a saved role selected even when it is no longer offered.
	 *
	 * @param string $value
	 */
	protected function render_single( $value ) {
		$options = $this->options;

		if ( $value && ! isset( $this->options[ $value ] ) ) {
			$names = wp_roles()->get_names();
			$name  = isset( $names[ $value ] ) ? translate_user_role( $names[ $value ] ) : $value;

			$this->options[ $value ] = sprintf(
				/* translators: %s: role name */
				__( '%s (not allowed)', 'automatewoo' ),
				$name
			);
		}

		parent::render_single( $value );

		$this->options = $options;
	}
}

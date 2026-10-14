<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Custom_Function
 */
class Action_Custom_Function extends Action {


	/**
	 * Method to set the action's admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Custom Function', 'automatewoo' );
		$this->description = sprintf(
			/* translators: %1$s documentation link start, %2$s documentation link end. */
			__( 'This action can be used by developers to trigger custom code from a workflow. %1$sView documentation%2$s.', 'automatewoo' ),
			'<a href="' . Admin::get_docs_link( 'actions/custom-functions/' ) . '">',
			'</a>'
		);
	}


	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$function_name = new Fields\Text();
		$function_name->set_title( __( 'Function name', 'automatewoo' ) );
		$function_name->set_name( 'function_name' );

		$this->add_field( $function_name );
	}


	/**
	 * Run the action.
	 *
	 * @throws \Exception When the function is not allowed or fails.
	 */
	public function run() {
		$function = $this->get_option( 'function_name' );

		if ( ! $this->is_function_allowed( $function ) ) {
			throw new \Exception(
				esc_html(
					sprintf(
						/* translators: 1: function name, 2: filter name. */
						__( 'The function “%1$s” can’t be run by this action. Use the %2$s filter to allow it.', 'automatewoo' ),
						is_string( $function ) ? $function : '',
						'automatewoo/custom_function/is_function_allowed'
					)
				)
			);
		}

		try {
			call_user_func( trim( $function ), $this->workflow );
		} catch ( \Error $e ) {
			throw new \Exception( esc_html( $e->getMessage() ), (int) $e->getCode(), $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message is escaped.
		}
	}


	/**
	 * Whether the action may call the given function.
	 *
	 * Functions built into PHP and functions defined by WordPress, WooCommerce or AutomateWoo are refused.
	 *
	 * @since 6.9.3
	 *
	 * @param mixed $function_name
	 * @return bool
	 */
	public function is_function_allowed( $function_name ): bool {
		$allowed = false;
		$name    = is_string( $function_name ) ? trim( $function_name ) : '';

		if ( '' !== $name && false === strpos( $name, '::' ) && function_exists( $name ) ) {
			$reflection = new \ReflectionFunction( $name );
			$file       = $reflection->isInternal() ? false : $reflection->getFileName();

			$allowed = is_string( $file ) && ! $this->is_in_protected_path( $file );
		}

		/**
		 * Filters whether the Custom Function action may call a function.
		 *
		 * @since 6.9.3
		 *
		 * @param bool                   $allowed Whether the function may be called.
		 * @param string                 $name    The trimmed function name.
		 * @param Action_Custom_Function $action  The action.
		 */
		return (bool) apply_filters( 'automatewoo/custom_function/is_function_allowed', $allowed, $name, $this );
	}


	/**
	 * Whether the current user may save the function names in the incoming actions.
	 *
	 * Names that are already stored may always be saved. Any other name needs an administrator.
	 *
	 * @since 6.9.3
	 *
	 * @param array $incoming_actions Actions as posted (arrays) or as built from a REST request (Entity\Action objects).
	 * @param array $stored_actions   The raw stored actions, read before any write.
	 * @return bool
	 */
	public static function can_current_user_save_function_names( array $incoming_actions, array $stored_actions ): bool {
		$new_names = array_diff( self::get_function_names( $incoming_actions ), self::get_function_names( $stored_actions ) );

		if ( ! $new_names ) {
			return true;
		}

		return is_multisite() ? is_super_admin() : current_user_can( 'manage_options' );
	}

	/**
	 * Get the message shown when the current user may not save a Custom Function name.
	 *
	 * @since 6.9.3
	 *
	 * @return string
	 */
	public static function get_function_name_permission_message(): string {
		return is_multisite()
			? __( 'Only super admins can set a Custom Function name.', 'automatewoo' )
			: __( 'Only administrators can set a Custom Function name.', 'automatewoo' );
	}


	/**
	 * Get the cleaned, lowercase function names of the Custom Function actions.
	 *
	 * @since 6.9.3
	 *
	 * @param array $actions Action arrays or Entity\Action objects.
	 * @return string[]
	 */
	public static function get_function_names( array $actions ): array {
		$names = [];

		foreach ( $actions as $action ) {
			if ( $action instanceof Entity\Action ) {
				$action_name = $action->get_name();
				$options     = $action->get_options();
			} elseif ( is_array( $action ) ) {
				$action_name = $action['action_name'] ?? '';
				$options     = $action;
			} else {
				continue;
			}

			if ( ! is_string( $action_name ) || 'custom_function' !== Clean::string( $action_name ) ) {
				continue;
			}

			$name = is_array( $options ) ? ( $options['function_name'] ?? '' ) : '';
			$name = strtolower( trim( Clean::string( is_string( $name ) ? $name : '' ) ) );

			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return array_values( array_unique( $names ) );
	}


	/**
	 * Get the normalized directories whose functions the action refuses to call.
	 *
	 * @since 6.9.3
	 *
	 * @return string[]
	 */
	public function get_protected_paths(): array {
		$paths = [ ABSPATH . WPINC, ABSPATH . 'wp-admin', AUTOMATEWOO_PATH ];

		if ( defined( 'WC_ABSPATH' ) ) {
			$paths[] = WC_ABSPATH;
		}

		/**
		 * Filters the directories whose functions the Custom Function action refuses to call.
		 *
		 * @since 6.9.3
		 *
		 * @param string[] $paths Raw directory paths, as defined by the constants. They are resolved after filtering.
		 */
		$paths = apply_filters( 'automatewoo/custom_function/protected_paths', $paths );

		$result = [];
		foreach ( (array) $paths as $path ) {
			if ( is_string( $path ) && '' !== $path && is_dir( $path ) ) {
				$result[] = trailingslashit( $this->normalize_path( $path ) );
			}
		}

		return array_values( array_unique( $result ) );
	}


	/**
	 * @param string $file
	 * @return bool
	 */
	private function is_in_protected_path( string $file ): bool {
		$file = $this->normalize_path( $file );

		foreach ( $this->get_protected_paths() as $path ) {
			if ( 0 === strncmp( $file, $path, strlen( $path ) ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Resolve symlinks where possible and normalize slashes and case.
	 *
	 * @param string $path
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		$resolved = realpath( $path );
		$path     = wp_normalize_path( false !== $resolved ? $resolved : $path );

		return in_array( PHP_OS_FAMILY, [ 'Windows', 'Darwin' ], true ) ? strtolower( $path ) : $path;
	}
}

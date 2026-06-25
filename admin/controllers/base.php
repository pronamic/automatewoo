<?php

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Admin;
use AutomateWoo\Clean;

/**
 * Base admin controller class
 *
 * @since 3.2.4
 */
abstract class Base {

	/** @var string */
	public $name;

	/** @var array */
	private $messages = [];

	/** @var array  */
	private $errors = [];

	/** @var string */
	protected $default_route = 'list';

	/** @var string */
	protected $heading;

	/** @var array */
	protected $heading_links = [];


	/**
	 * Handle controller requests
	 *
	 * @return void
	 */
	abstract public function handle();


	/**
	 * @return string
	 */
	public function get_heading() {
		if ( isset( $this->heading ) ) {
			return $this->heading;
		}
		return get_admin_page_title();
	}


	/**
	 * @return array
	 */
	public function get_heading_links() {
		return $this->heading_links;
	}


	/**
	 * Output stored admin notices.
	 *
	 * @return void
	 */
	public function output_messages() {

		if ( sizeof( $this->errors ) > 0 ) {
			foreach ( $this->errors as $error ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_notice() returns trusted HTML; main text is escaped internally.
				echo $this->format_notice( $error, 'error' );
			}
		} elseif ( sizeof( $this->messages ) > 0 ) {
			foreach ( $this->messages as $message ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_notice() returns trusted HTML; main text is escaped internally.
				echo $this->format_notice( $message, 'success' );
			}
		}
	}


	/**
	 * @param array|string $notice_data
	 * @param string       $type
	 * @return string
	 */
	public function format_notice( $notice_data, $type ) {

		$class = "notice notice-$type automatewoo-notice";

		if ( is_array( $notice_data ) ) {
			$main_text  = $notice_data['main'];
			$extra_text = isset( $notice_data['extra'] ) ? $notice_data['extra'] : '';
			$class     .= ' ' . $notice_data['class'];
		} else {
			$main_text  = $notice_data;
			$extra_text = '';
		}

		return '<div class="' . $class . '"><p><strong>' . esc_html( $main_text ) . '</strong> ' . $extra_text . '</p></div>';
	}


	/**
	 * @return string
	 */
	public function get_messages() {
		ob_start();
		$this->output_messages();
		return ob_get_clean();
	}


	/**
	 * @return string
	 */
	public function get_current_action() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only admin action read; sanitized via Clean::string(), no state change.
		$action = isset( $_REQUEST['action'] ) ? Clean::string( wp_unslash( $_REQUEST['action'] ) ) : '-1';
		if ( '-1' !== $action ) {
			return $action;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only admin action read; sanitized via Clean::string(), no state change.
		$action2 = isset( $_REQUEST['action2'] ) ? Clean::string( wp_unslash( $_REQUEST['action2'] ) ) : '-1';
		if ( '-1' !== $action2 ) {
			return $action2;
		}

		return $this->default_route;
	}


	/**
	 * @return string
	 */
	public function get_nonce_action() {
		return 'automatewoo-' . $this->name;
	}


	/**
	 * Verify nonce
	 *
	 * @param bool|string $nonce_action - optional custom nonce
	 */
	public function verify_nonce_action( $nonce_action = false ) {
		$nonce = Clean::string( aw_request( '_wpnonce' ) );

		if ( ! $nonce_action ) {
			$nonce_action = $this->get_nonce_action();
		}

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die( 'Security check failed.' );
		}
	}


	/**
	 * @param string $main_text
	 * @param string $extra_text
	 * @param string $extra_classes
	 */
	public function add_message( $main_text, $extra_text = '', $extra_classes = '' ) {
		$this->messages[] = [
			'main'  => $main_text,
			'extra' => $extra_text,
			'class' => $extra_classes,
		];
	}


	/**
	 * @param string $main_text
	 * @param string $extra_text
	 * @param string $extra_classes
	 */
	public function add_error( $main_text, $extra_text = '', $extra_classes = '' ) {
		$this->errors[] = [
			'main'  => $main_text,
			'extra' => $extra_text,
			'class' => $extra_classes,
		];
	}


	/**
	 * @return string
	 */
	public function get_responses_option_name() {
		return '_automatewoo_admin_temp_messages_' . get_current_user_id();
	}


	/**
	 * Persist current messages and errors for the next request.
	 *
	 * @return void
	 */
	public function store_responses() {
		update_option(
			$this->get_responses_option_name(),
			[
				'errors'   => $this->errors,
				'messages' => $this->messages,
			],
			false
		);
	}


	/**
	 * Load stored messages and errors from the previous request.
	 *
	 * @return void
	 */
	public function load_stored_responses() {
		$store = get_option( $this->get_responses_option_name() );
		if ( $store ) {
			$this->messages = $store['messages'];
			$this->errors   = $store['errors'];
		}
		$this->clear_stored_responses();
	}


	/**
	 * Delete the stored responses option.
	 *
	 * @return void
	 */
	public function clear_stored_responses() {
		delete_option( $this->get_responses_option_name() );
	}


	/**
	 * @param string $action
	 * @param array  $query_args
	 */
	public function redirect_after_action( $action = '', $query_args = [] ) {

		$this->store_responses();

		$args = [
			'did-action' => $this->get_current_action(),
		];

		if ( $action ) {
			$args['action'] = $action;
		}

		$query_args = array_merge( $args, $query_args );

		wp_safe_redirect( add_query_arg( $query_args, Admin::page_url( $this->name ) ), 302 );
		exit;
	}


	/**
	 * Outputs an controller view.
	 * Adds relevant variables to scope.
	 *
	 * IMPORTANT not to name $import_variables something as $args
	 * can cause errors if there is a conflicting key name in the array
	 *
	 * @param string      $view
	 * @param array       $imported_variables
	 * @param bool|string $path
	 */
	public function output_view( $view, $imported_variables = [], $path = false ) {

		$imported_variables['controller'] = $this;
		$imported_variables['page']       = $this->name;
		$imported_variables['heading']    = $this->get_heading();
		$imported_variables['messages']   = $this->get_messages();

		if ( $imported_variables && is_array( $imported_variables ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controller views rely on extracted variables; input is internal, not user-supplied.
			extract( $imported_variables );
		}

		if ( $path ) {
			if ( ! file_exists( "$path/$view.php" ) ) {
				$path = false; // fall back to original views dir
			}
		}

		if ( ! $path ) {
			$path = AW()->admin_path( '/views' );
		}

		include "$path/$view.php"; // nosemgrep All the calls to this function are internal, w/o user input.
	}
}

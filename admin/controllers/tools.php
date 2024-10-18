<?php

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Clean;
use AutomateWoo\Tool_Abstract;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for the tools page.
 *
 * @class Tools_Controller
 */
class Tools_Controller extends Base {
	/**
	 * Handle requests to the tools page.
	 *
	 * @return void
	 */
	public function handle() {
		$action  = $this->get_current_action();
		$tool_id = Clean::string( aw_request( 'tool_id' ) );
		$tool    = AW()->tools_service()->get_tool( $tool_id );

		if ( $this->default_route !== $action && ! $tool ) {
			wp_die( esc_html__( 'Invalid tool.', 'automatewoo' ) );
		}

		switch ( $action ) {
			case 'view':
				if ( $tool->get_form_fields() ) {
					$this->output_view_form( $tool_id );
				} else {
					// Jump to confirm view if no fields are present.
					$this->output_view_confirm( $tool_id );
				}
				break;

			case 'validate':
				if ( $this->validate_process( $tool_id ) ) {
					$this->output_view_confirm( $tool_id );
				} else {
					$this->output_view_form( $tool_id );
				}

				break;

			case 'confirm':
				$this->confirm_process( $tool_id );
				$this->output_view_listing();
				break;

			default:
				$this->output_view_listing();
		}

		wp_enqueue_script( 'automatewoo-tools' );
	}

	/**
	 * Output the view for the default tools page.
	 *
	 * @return void
	 */
	private function output_view_listing() {
		$this->output_view(
			'page-tools-list',
			[
				'tools' => AW()->tools_service()->get_tools(),
			]
		);
	}


	/**
	 * Output a form view for a given tool
	 *
	 * @param string $tool_id The ID of the current tool.
	 *
	 * @return void
	 */
	private function output_view_form( $tool_id ) {
		$tool = AW()->tools_service()->get_tool( $tool_id );

		$this->output_view(
			'page-tools-form',
			[
				'tool' => $tool,
			]
		);
	}


	/**
	 * Output the confirmation view for a given tool
	 *
	 * @param string $tool_id The ID of the current tool.
	 *
	 * @return void
	 */
	private function output_view_confirm( $tool_id ) {
		$tool = AW()->tools_service()->get_tool( $tool_id );
		$args = $tool->sanitize_args( aw_request( 'args' ) );

		$this->output_view(
			'page-tools-form-confirm',
			[
				'tool' => $tool,
				'args' => $args,
			]
		);
	}

	/**
	 * Return true if init was successful
	 *
	 * @param string $tool_id The ID of the current tool.
	 *
	 * @return bool
	 */
	private function validate_process( $tool_id ) {
		$tool = AW()->tools_service()->get_tool( $tool_id );
		$args = $tool->sanitize_args( aw_request( 'args' ) );

		if ( ! $tool ) {
			wp_die( esc_html__( 'Invalid tool.', 'automatewoo' ) );
		}

		$valid = $tool->validate_process( $args );

		if ( false === $valid ) {
			$this->add_error( __( 'Failed to init process.', 'automatewoo' ) );
			return false;
		} elseif ( is_wp_error( $valid ) ) {
			$this->add_error( $valid->get_error_message() );
			return false;
		} elseif ( true === $valid ) {
			return true;
		}
		return false;
	}

	/**
	 * Run security checks and process the tool after confirmation
	 *
	 * @param string $tool_id The ID of the current tool.
	 *
	 * @return void
	 */
	private function confirm_process( $tool_id ) {

		$nonce = Clean::string( aw_request( '_wpnonce' ) );

		if ( ! wp_verify_nonce( $nonce, $tool_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'automatewoo' ) );
		}

		// Process should be valid at this point but just in case.
		if ( ! $this->validate_process( $tool_id ) ) {
			wp_die( esc_html__( 'Process could not be validated.', 'automatewoo' ) );
		}

		$tool = AW()->tools_service()->get_tool( $tool_id );
		$args = aw_request( 'args' );

		$processed = $tool->process( $args );

		if ( false === $processed ) {
			$this->add_error( __( 'Process failed.', 'automatewoo' ) );
		} elseif ( is_wp_error( $processed ) ) {
			$this->add_error( $processed->get_error_message() );
		} elseif ( true === $processed ) {
			$this->add_message( __( 'Success - Items may be still be processing in the background.', 'automatewoo' ) );
		}
	}

	/**
	 * Get URL for the main tools page or a specific tool.
	 *
	 * @param string|bool        $route Value to set for the action query arg.
	 * @param Tool_Abstract|bool $tool  Tool to set for the tool_id query arg.
	 *
	 * @return string
	 */
	public function get_route_url( $route = false, $tool = false ) {
		$base_url = admin_url( 'admin.php?page=automatewoo-tools' );

		if ( ! $route ) {
			return $base_url;
		}

		// SEMGREP WARNING EXPLANATION
		// This is being escaped later in the consumer call (if not, a warning will be produced by PHPCS).
		return add_query_arg(
			[
				'action'  => $route,
				'tool_id' => $tool->get_id(),
			],
			$base_url
		);
	}
}

return new Tools_Controller();

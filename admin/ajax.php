<?php

namespace AutomateWoo;

use AutomateWoo\Admin\JSON_Search;
use AutomateWoo\Exceptions\InvalidPreviewData;
use AutomateWoo\Workflows\Factory;
use WP_Error;

/**
 * @class Admin_Ajax
 */
class Admin_Ajax {

	/**
	 * Hook in methods
	 */
	public static function init() {
		$ajax_events = [
			'fill_trigger_fields',
			'fill_action_fields',
			'json_search_workflows',
			'json_search_coupons',
			'json_search_coupons_recurring',
			'json_search_attribute_terms',
			'json_search_taxonomy_terms',
			'json_search_customers',
			'json_search_products_and_variations_not_variable',
			'json_search_products_not_variations_not_variable',
			'json_search_sensei_courses',
			'json_search_sensei_lessons',
			'json_search_sensei_quizzes',
			'json_search_sensei_questions',
			'json_search_sensei_groups',
			'activate',
			'deactivate',
			'email_preview_iframe',
			'test_sms',
			'database_update',
			'database_update_items_to_process_count',
			'save_preview_data',
			'send_test_email',
			'dismiss_system_error_notice',
			'get_rule_select_choices',
			'toggle_workflow_status',
			'modal_log_info',
			'modal_queue_info',
			'modal_variable_info',
			'modal_cart_info',
			'update_dynamic_action_select',
			'update_dynamic_trigger_options_select',
		];

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_aw_' . $ajax_event, [ __CLASS__, $ajax_event ] );
		}
	}


	/**
	 * Retrieve trigger fields and echo JSON.
	 */
	public static function fill_trigger_fields() {

		if ( ! self::is_authorized() ) {
			die;
		}

		$trigger_name    = Clean::string( aw_request( 'trigger_name' ) );
		$workflow_id     = absint( aw_request( 'workflow_id' ) );
		$is_new_workflow = filter_var( aw_request( 'is_new_workflow' ), FILTER_VALIDATE_BOOLEAN );

		$workflow = false;
		$trigger  = Triggers::get( $trigger_name );

		if ( ! $trigger ) {
			die;
		}

		if ( ! $is_new_workflow ) {
			$workflow = new Workflow( $workflow_id );
		}

		ob_start();

		Admin::get_view(
			'trigger-fields',
			[
				'trigger'  => $trigger,
				'workflow' => $workflow,
			]
		);

		$fields = ob_get_clean();

		wp_send_json_success(
			[
				'fields'  => $fields,
				'trigger' => Admin_Workflow_Edit::get_trigger_data( $trigger ),
			]
		);
	}


	/**
	 * Retrieve action fields and echo JSON.
	 */
	public static function fill_action_fields() {

		if ( ! self::is_authorized() ) {
			die;
		}

		$action_name   = Clean::string( aw_request( 'action_name' ) );
		$action_number = Clean::string( aw_request( 'action_number' ) );

		if ( '' === $action_name ) {
			wp_send_json_success(
				[
					'fields'      => '',
					'title'       => 'New Action',
					'description' => '',
				]
			);
		}

		$action = Actions::get( $action_name );

		ob_start();

		Admin::get_view(
			'action-fields',
			[
				'action'        => $action,
				'action_number' => $action_number,
			]
		);

		$fields = ob_get_clean();

		wp_send_json_success(
			[
				'fields'      => $fields,
				'title'       => $action->get_title( true ),
				'description' => $action->get_description_html(),
			]
		);
	}


	/**
	 * Search for workflows and echo JSON.
	 */
	public static function json_search_workflows() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		ob_start();

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::workflows( $term );
	}


	/**
	 * Search customers, includes guests customers
	 */
	public static function json_search_customers() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		ob_start();

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::customers( $term );
	}

	/**
	 * Search for products and variations, but not variable products.
	 */
	public static function json_search_products_and_variations_not_variable() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::products( $term, true, false );
	}

	/**
	 * Search for products excluding variable and variation products.
	 */
	public static function json_search_products_not_variations_not_variable() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::products( $term, false, false );
	}


	/**
	 * Search for products and echo json
	 */
	public static function json_search_attribute_terms() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin search; values sanitized below, no state change.
		if ( empty( $_GET['term'] ) || empty( $_GET['sibling'] ) ) {
			die;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only admin search; sanitized via Clean::string(), no state change.
		$search = Clean::string( wp_unslash( $_GET['term'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only admin search; sanitized via Clean::string(), no state change.
		$sibling = Clean::string( wp_unslash( $_GET['sibling'] ) );

		$terms = get_terms(
			[
				'taxonomy'   => 'pa_' . $sibling,
				'hide_empty' => false,
				'search'     => $search,
			]
		);

		$found = [];

		if ( ! $terms || is_wp_error( $terms ) ) {
			die();
		}

		foreach ( $terms as $term ) {
			$found[ $term->term_id . '|' . $term->taxonomy ] = rawurldecode( $term->name );
		}

		wp_send_json( $found );
	}



	/**
	 * Search for products and echo json
	 */
	public static function json_search_taxonomy_terms() {
		if ( ! Permissions::can_manage() ) {
			die;
		}

		ob_start();

		$search  = Clean::string( stripslashes( aw_get_url_var( 'term' ) ) );
		$sibling = Clean::string( stripslashes( aw_get_url_var( 'sibling' ) ) );

		if ( empty( $search ) || empty( $sibling ) ) {
			die;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $sibling,
				'hide_empty' => false,
				'search'     => $search,
			]
		);

		$found = [];

		if ( ! $terms || is_wp_error( $terms ) ) {
			die;
		}

		foreach ( $terms as $term ) {
			$found[ $term->term_id . '|' . $term->taxonomy ] = rawurldecode( $term->name );
		}

		wp_send_json( $found );
	}

	/**
	 * Search for coupons and echo JSON.
	 */
	public static function json_search_coupons() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::coupons( $term, true, false );
	}

	/**
	 * Search for recurring coupons and echo JSON.
	 */
	public static function json_search_coupons_recurring() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::coupons( $term, true, true );
	}

	/**
	 * Search for sensei courses and echo JSON.
	 *
	 * @since 5.6.10
	 */
	public static function json_search_sensei_courses() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_sensei is a capability registered by the Sensei LMS plugin.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::sensei_data( $term, 'course' );
	}

	/**
	 * Search for sensei lessons and echo JSON.
	 *
	 * @since 5.6.10
	 */
	public static function json_search_sensei_lessons() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_sensei is a capability registered by the Sensei LMS plugin.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::sensei_data( $term, 'lesson' );
	}

	/**
	 * Search for sensei quizzes and echo JSON.
	 *
	 * @since 5.6.10
	 */
	public static function json_search_sensei_quizzes() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_sensei is a capability registered by the Sensei LMS plugin.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::sensei_data( $term, 'quiz' );
	}

	/**
	 * Search for sensei questions and echo JSON.
	 *
	 * @since 5.6.10
	 */
	public static function json_search_sensei_questions() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_sensei is a capability registered by the Sensei LMS plugin.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::sensei_data( $term, 'question' );
	}

	/**
	 * Search for sensei groups and echo JSON.
	 *
	 * @since 5.6.10
	 */
	public static function json_search_sensei_groups() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_sensei is a capability registered by the Sensei LMS plugin.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			die;
		}

		$term = Clean::string( wp_unslash( aw_get_url_var( 'term' ) ) );
		JSON_Search::sensei_data( $term, 'group' );
	}

	/**
	 * Render the email preview iframe content.
	 */
	public static function email_preview_iframe() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$type = Clean::string( aw_request( 'type' ) );
		$args = Clean::recursive( aw_request( 'args' ) );

		switch ( $type ) {

			case 'workflow_action':
				try {
					$action = Preview_Data::generate_preview_action( $args['workflow_id'], $args['action_number'] );
				} catch ( InvalidPreviewData $e ) {
					return wp_die( esc_html( $e->getMessage() ) );
				}

				do_action( 'automatewoo/action/before_preview', $action );

				$action->workflow->setup();

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Email preview HTML is generated and escaped by the action's preview renderer.
				echo $action->get_preview();

				$action->workflow->cleanup();

				do_action( 'automatewoo/action/after_preview', $action );

				break;

			default:
				do_action( 'automatewoo/email_preview/html', $type, $args );
		}

		exit();
	}


	/**
	 * Sends a test to supplied emails
	 */
	public static function send_test_email() {

		if ( ! self::is_authorized() ) {
			die;
		}

		$type = Clean::string( aw_request( 'type' ) );
		$args = Clean::recursive( aw_request( 'args' ) );
		$to   = Clean::string( aw_request( 'to_emails' ) );

		// save the to field
		update_user_meta( get_current_user_id(), 'automatewoo_email_preview_test_emails', $to );

		$to = Emails::parse_multi_email_field( $to );

		switch ( $type ) {

			case 'workflow_action':
				try {
					$action = Preview_Data::generate_preview_action( $args['workflow_id'], $args['action_number'], 'test' );
				} catch ( InvalidPreviewData $e ) {
					return wp_die( esc_html( $e->getMessage() ) );
				}

				$action->workflow->setup();
				$current_user = get_current_user_id();
				// Temporarily remove the current user since no current user is typically exists when running a workflow
				// phpcs:ignore
				wp_set_current_user( 0 );

				$result = $action->run_test( [ 'recipients' => $to ] );

				// Rollback to the previous user.
				// phpcs:ignore
				wp_set_current_user( $current_user );
				$action->workflow->cleanup();

				break;

			default:
				do_action( 'automatewoo/email_preview/send_test', $type, $to, $args );
				$result = false;
		}

		if ( $result instanceof WP_Error ) {
			wp_send_json_error(
				[
					/* translators: %s: The error message. */
					'message' => sprintf( __( 'Error: %s', 'automatewoo' ), $result->get_error_message() ),
				]
			);
		}

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %s: Number of emails sent. */
					_n( 'Success! %s email sent', 'Success! %s emails sent', count( $to ), 'automatewoo' ),
					count( $to )
				),
			]
		);
	}



	/**
	 * Send a test SMS message.
	 */
	public static function test_sms() {
		if ( ! self::is_authorized() ) {
			die;
		}

		$from           = Clean::string( aw_request( 'from' ) );
		$auth_id        = Clean::string( aw_request( 'auth_id' ) );
		$auth_token     = Clean::string( aw_request( 'auth_token' ) );
		$test_message   = Clean::string( aw_request( 'test_message' ) );
		$test_recipient = Clean::string( aw_request( 'test_recipient' ) );

		$twilio = new Integration_Twilio( $from, $auth_id, $auth_token );

		$twilio->log_errors = false; // errors will be visible

		$request = $twilio->send_sms( $test_recipient, $test_message, $from );

		if ( $request->is_successful() ) {
			wp_send_json_success(
				[
					'message' => __( 'Message sent.', 'automatewoo' ),
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => $twilio->get_request_error_message( $request ),
				]
			);
		}
	}


	/**
	 * Run a database update for the plugin or an add-on.
	 */
	public static function database_update() {

		$nonce       = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		$verify      = wp_verify_nonce( $nonce, 'automatewoo_database_upgrade' );
		$plugin_slug = Clean::string( aw_request( 'plugin_slug' ) );

		if ( ! $verify ) {
			wp_send_json_error( __( 'Permission error.', 'automatewoo' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		if ( $plugin_slug === AW()->plugin_slug ) {
			// updating the primary plugin
			$complete = Installer::run_database_updates();

			wp_send_json_success(
				[
					'complete'        => $complete,
					'items_processed' => Installer::$db_update_items_processed,
				]
			);
		} else {
			// updating an addon
			$addon = Addons::get( $plugin_slug );

			if ( ! $addon ) {
				wp_send_json_error( __( 'Add-on could not be updated', 'automatewoo' ) );
			}

			$addon->do_database_update();

			wp_send_json_success(
				[
					'complete' => true,
				]
			);
		}
	}


	/**
	 * Return the number of items to process for a database update.
	 */
	public static function database_update_items_to_process_count() {
		if ( ! self::is_authorized() ) {
			die;
		}

		$plugin_slug = Clean::string( aw_request( 'plugin_slug' ) );

		if ( $plugin_slug === AW()->plugin_slug ) {
			$count = Installer::get_database_update_items_to_process_count();
		} else {
			$count = 0; // batch processor not yet supported for addons
		}

		wp_send_json_success(
			[
				'items_to_process' => $count,
			]
		);
	}


	/**
	 * To preview an action save temporarily in the options table.
	 */
	public static function save_preview_data() {
		if ( ! self::is_authorized() ) {
			die;
		}

		$workflow      = Factory::get( aw_get_post_var( 'workflow_id' ) );
		$trigger_name  = Clean::string( aw_get_post_var( 'trigger_name' ) );
		$action_fields = $workflow->sanitize_action_fields( aw_get_post_var( 'action_fields' ) );

		if ( ! $trigger_name || ! is_array( $action_fields ) || ! $workflow ) {
			wp_send_json_error();
		}

		$preview_data = [
			'trigger_name'  => $trigger_name,
			'action_fields' => $action_fields,
		];

		update_option( 'aw_wf_preview_data_' . $workflow->get_id(), $preview_data, false );

		wp_send_json_success();
	}


	/**
	 * Dismiss the background system error notice.
	 */
	public static function dismiss_system_error_notice() {
		if ( ! self::is_authorized() ) {
			die;
		}

		delete_transient( 'automatewoo_background_system_check_errors' );

		wp_send_json_success();
	}


	/**
	 * Return select choices for a rule.
	 */
	public static function get_rule_select_choices() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$rule_name = Clean::string( aw_request( 'rule_name' ) );
		if ( ! $rule_name ) {
			die;
		}

		$rule_object = Rules::get( $rule_name );

		if ( $rule_object->type === 'select' ) {
			wp_send_json_success(
				[
					'select_choices' => $rule_object->get_select_choices(),
				]
			);
		}

		die;
	}


	/**
	 * Display content for log details modal
	 */
	public static function modal_log_info() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$log = Log_Factory::get( absint( aw_request( 'log_id' ) ) );
		if ( $log ) {
			Admin::get_view( 'modal-log-info', [ 'log' => $log ] );
			die;
		}

		die( esc_html__( 'No log found.', 'automatewoo' ) );
	}


	/**
	 * Display content for queued event details modal.
	 */
	public static function modal_queue_info() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$event = Queued_Event_Factory::get( absint( aw_request( 'queued_event_id' ) ) );
		if ( $event ) {
			Admin::get_view( 'modal-queued-event-info', [ 'event' => $event ] );
			die;
		}

		die( esc_html__( 'No queued event found.', 'automatewoo' ) );
	}


	/**
	 * Display content for variable details modal.
	 */
	public static function modal_variable_info() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$variable = Variables::get_variable( Clean::string( aw_request( 'variable' ) ) );

		if ( $variable ) {
			Admin::get_view(
				'modal-variable-info',
				[
					'variable' => $variable,
				]
			);
			die;
		}

		wp_die( esc_html__( 'Variable not found.', 'automatewoo' ) );
	}


	/**
	 * Display content for cart details modal.
	 */
	public static function modal_cart_info() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die;
		}

		$cart = Cart_Factory::get( absint( aw_request( 'cart_id' ) ) );
		if ( $cart ) {
			Admin::get_view( 'modal-cart-info', [ 'cart' => $cart ] );
			die;
		}

		die( esc_html__( 'No cart found.', 'automatewoo' ) );
	}



	/**
	 * Toggle a workflow's active status.
	 */
	public static function toggle_workflow_status() {

		if ( ! self::is_authorized() ) {
			die;
		}

		$workflow  = Factory::get( aw_request( 'workflow_id' ) );
		$new_state = Clean::string( aw_request( 'new_state' ) );

		if ( ! $workflow || ! $new_state ) {
			die;
		}

		$workflow->update_status( $new_state === 'on' ? 'active' : 'disabled' );

		wp_send_json_success();
	}


	/**
	 * Respond with dynamic field options for a given action field.
	 */
	public static function update_dynamic_action_select() {

		if ( ! self::is_authorized() ) {
			die;
		}

		$action_name           = Clean::string( aw_request( 'action_name' ) );
		$target_field_name     = Clean::string( aw_request( 'target_field_name' ) );
		$reference_field_value = Clean::string( aw_request( 'reference_field_value' ) );

		$options = [];

		if ( $reference_field_value ) {
			$action  = Actions::get( $action_name );
			$options = $action->get_dynamic_field_options( $target_field_name, $reference_field_value );
		}

		wp_send_json_success( $options );
	}

	/**
	 * Respond dynamic field options for a given trigger field.
	 *
	 * @since 5.6.6
	 */
	public static function update_dynamic_trigger_options_select() {
		if ( ! self::is_authorized() ) {
			die;
		}

		$trigger_name          = Clean::string( aw_request( 'trigger_name' ) );
		$target_field_name     = Clean::string( aw_request( 'target_field_name' ) );
		$reference_field_value = Clean::string( aw_request( 'reference_field_value' ) );

		$options = [];

		if ( $reference_field_value ) {
			$trigger = Triggers::get( $trigger_name );
			$options = $trigger->get_dynamic_field_options( $target_field_name, $reference_field_value );
		}

		wp_send_json_success( $options );
	}

	/**
	 * Verifies if user is authorized to perform an ajax request.
	 * We do so by checking if is able to manage WooCommerce and also checking the nonce.
	 *
	 * @since 5.7.6
	 *
	 * @return bool True if is authorized, false otherwise
	 */
	public static function is_authorized() {
		$nonce = Clean::string( aw_request( 'nonce' ) );

		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( $nonce, aw_request( 'action' ) ) ) {
			return false;
		}

		return true;
	}
}

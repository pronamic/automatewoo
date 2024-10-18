<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Tool_Optout_Importer
 * @since 3.9
 */
class Tool_Optout_Importer extends Tool_Background_Processed_Abstract {

	public $id = 'optout_importer';


	function __construct() {
		parent::__construct();
		$this->title = __( 'Opt-out Importer', 'automatewoo' );
		$this->description = __( "Opt-out customers by importing email addresses.", 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function get_form_fields() {
		$fields = [];

		$fields[] = ( new Fields\Text_Area() )
			->set_name( 'emails' )
			->set_title( __( 'Emails', 'automatewoo' ) )
			->set_name_base( 'args' )
			->set_rows( 20 )
			->set_placeholder( __( 'Add one email per line...', 'automatewoo' ) )
			->set_required();

		return $fields;
	}


	/**
	 * Parse emails but don't actually check if they are valid
	 *
	 * @param $emails
	 * @return array
	 */
	function parse_emails( $emails ) {
		$emails = explode( PHP_EOL, $emails );
		$emails = array_map( 'trim', $emails );
		$emails = array_map( 'stripslashes', $emails);

		return $emails;
	}


	/**
	 * @param array $args sanitized
	 * @return bool|\WP_Error
	 */
	function validate_process( $args ) {
		if ( empty( $args['emails'] ) ) {
			return new \WP_Error( 1, __( 'Missing a required field.', 'automatewoo') );
		}

		$emails = $this->parse_emails( $args['emails'] );

		foreach( $emails as $email ) {
			if ( ! is_email( $email ) ) {
				return new \WP_Error( 3, sprintf( __( '%s is not a valid email.', 'automatewoo' ), $email ) );
			}
		}

		return true;
	}


	/**
	 * @param $args
	 * @return bool|\WP_Error
	 */
	function process( $args ) {
		$args = $this->sanitize_args( $args );
		$emails = $this->parse_emails( $args['emails'] );

		if ( empty( $emails ) ) {
			return new \WP_Error( 2, __( 'Could not process.', 'automatewoo') );
		}

		$tasks = [];

		foreach ( $emails as $email ) {
			$tasks[] = [
				'tool_id' => $this->get_id(),
				'email' => $email,
			];
		}

		return $this->start_background_job( $tasks );
	}


	/**
	 * @param $args
	 */
	function display_confirmation_screen( $args ) {
		$args = $this->sanitize_args( $args );
		$emails = $this->parse_emails( $args['emails'] );

		echo '<p>' .
			sprintf(
				__( 'Are you sure you want to opt-out <strong>%s customers</strong>?', 'automatewoo' ),
				count( $emails ) )
			. '</p>';

		$this->display_data_preview( $emails );
	}


	function display_data_preview( $items ) {
		$number_to_preview = 25;

		echo '<p>';

		foreach ( $items as $i => $email ) {

			if ( $i == $number_to_preview )
				break;

			echo $email . '<br>';
		}

		if ( count( $items ) > $number_to_preview ) {
			printf( __( '+ %d more items...', 'automatewoo' ), ( count( $items ) - $number_to_preview ) );
		}

		echo '</p>';
	}


	/**
	 * @param array $args
	 * @return array
	 */
	function sanitize_args( $args ) {
		$args = parent::sanitize_args( $args );

		if ( isset( $args['emails'] ) ) {
			$args['emails'] = Clean::textarea( $args['emails'] );
		}

		return $args;
	}


	/**
	 * @param array $task
	 */
	function handle_background_task( $task ) {
		$email = isset( $task['email'] ) ? Clean::email( $task['email'] ) : false;

		if ( ! $email ) {
			return;
		}

		if ( $customer = Customer_Factory::get_by_email( $email ) ) {
			$customer->opt_out();
		}
	}

}

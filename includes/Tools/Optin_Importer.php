<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Tool_Optin_Importer
 * @since 3.9
 */
class Tool_Optin_Importer extends Tool_Optout_Importer {

	public $id = 'optin_importer';


	function __construct() {
		parent::__construct();
		$this->title = __( 'Opt-in Importer', 'automatewoo' );
		$this->description = __( "Opt-in customers by importing email addresses.", 'automatewoo' );
	}


	/**
	 * @param $args
	 */
	function display_confirmation_screen( $args ) {
		$args = $this->sanitize_args( $args );
		$emails = $this->parse_emails( $args['emails'] );
		$count  = count( $emails );

		echo wp_kses_post(
			'<p>' . sprintf(
				/* translators: %d Number of customers to import for opt-in. */
				_n(
					'Are you sure you want to opt-in <strong>%d customer</strong>?',
					'Are you sure you want to opt-in <strong>%d customers</strong>?',
					$count,
					'automatewoo'
				),
				$count
			) . '</p>'
		);

		$this->display_data_preview( $emails );
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
			$customer->opt_in();
		}
	}

}

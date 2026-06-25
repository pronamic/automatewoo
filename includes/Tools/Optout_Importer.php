<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Tool_Optout_Importer
 * @since 3.9
 */
class Tool_Optout_Importer extends Tool_Background_Processed_Abstract {

	/**
	 * The tool ID.
	 *
	 * @var string
	 */
	public $id = 'optout_importer';


	/**
	 * Tool_Optout_Importer constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->title       = __( 'Opt-out Importer', 'automatewoo' );
		$this->description = __( 'Opt-out customers by importing email addresses.', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function get_form_fields() {
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
	 * @param string $emails
	 * @return array
	 */
	public function parse_emails( $emails ) {
		$emails = explode( PHP_EOL, $emails );
		$emails = array_map( 'trim', $emails );
		$emails = array_map( 'stripslashes', $emails );

		return $emails;
	}


	/**
	 * @param array $args sanitized
	 * @return bool|\WP_Error
	 */
	public function validate_process( $args ) {
		if ( empty( $args['emails'] ) ) {
			return new \WP_Error( 1, __( 'Missing a required field.', 'automatewoo' ) );
		}

		$emails = $this->parse_emails( $args['emails'] );

		foreach ( $emails as $email ) {
			if ( ! is_email( $email ) ) {
				return new \WP_Error(
					3,
					sprintf(
						/* translators: %s Invalid email. */
						__( '%s is not a valid email.', 'automatewoo' ),
						$email
					)
				);
			}
		}

		return true;
	}


	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	public function process( $args ) {
		$args   = $this->sanitize_args( $args );
		$emails = $this->parse_emails( $args['emails'] );

		if ( empty( $emails ) ) {
			return new \WP_Error( 2, __( 'Could not process.', 'automatewoo' ) );
		}

		$tasks = [];

		foreach ( $emails as $email ) {
			$tasks[] = [
				'tool_id' => $this->get_id(),
				'email'   => $email,
			];
		}

		return $this->start_background_job( $tasks );
	}


	/**
	 * @param array $args
	 */
	public function display_confirmation_screen( $args ) {
		$args   = $this->sanitize_args( $args );
		$emails = $this->parse_emails( $args['emails'] );

		echo wp_kses_post(
			'<p>' . sprintf(
				/* translators: %s Number of customers to import for opt-out. */
				__( 'Are you sure you want to opt-out <strong>%s customers</strong>?', 'automatewoo' ),
				count( $emails )
			) . '</p>'
		);

		$this->display_data_preview( $emails );
	}


	/**
	 * Output the icon legend in the confirmation screen footer.
	 */
	public function display_confirmation_legend() {
		echo wp_kses_post(
			'<p style="float: left;">' . sprintf(
				/* translators: %1$s and %2$s are status icons shown next to each email in the preview list. */
				__( '%1$s Existing customer &nbsp; %2$s New customer record will be created', 'automatewoo' ),
				'✅',
				'⚠️'
			) . '</p>'
		);
	}


	/**
	 * Output a preview of the emails to be imported.
	 *
	 * @param array $items
	 */
	public function display_data_preview( $items ) {
		$number_to_preview = 25;

		echo '<p>';

		foreach ( $items as $i => $email ) {

			if ( $i === $number_to_preview ) {
				break;
			}

			$icon = $this->email_matches_existing_customer( $email ) ? '✅' : '⚠️';

			echo esc_html( $email ) . ' ' . esc_html( $icon ) . '<br>';
		}

		if ( count( $items ) > $number_to_preview ) {
			printf(
				/* translators: %d Count of additional items. */
				esc_html__( '+ %d more items...', 'automatewoo' ),
				( count( $items ) - $number_to_preview )
			);
		}

		echo '</p>';
	}


	/**
	 * Check whether the email is already known to the store without creating a customer record.
	 *
	 * @param string $email
	 * @return bool
	 */
	public function email_matches_existing_customer( $email ) {
		$email = Clean::email( $email );

		if ( ! $email ) {
			return false;
		}

		if ( get_user_by( 'email', $email ) ) {
			return true;
		}

		return (bool) Guest_Factory::get_by_email( $email );
	}


	/**
	 * @param array $args
	 * @return array
	 */
	public function sanitize_args( $args ) {
		$args = parent::sanitize_args( $args );

		if ( isset( $args['emails'] ) ) {
			$args['emails'] = Clean::textarea( $args['emails'] );
		}

		return $args;
	}


	/**
	 * @param array $task
	 */
	public function handle_background_task( $task ) {
		$email = isset( $task['email'] ) ? Clean::email( $task['email'] ) : false;

		if ( ! $email ) {
			return;
		}

		$customer = Customer_Factory::get_by_email( $email );
		if ( $customer ) {
			$customer->opt_out();
		}
	}
}

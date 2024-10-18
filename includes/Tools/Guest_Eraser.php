<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guest_Eraser
 * @since 4.0
 */
class Guest_Eraser extends Tool_Background_Processed_Abstract {

	public $id = 'guest_eraser';


	function __construct() {
		parent::__construct();
		$this->title = __( 'Guest Eraser', 'automatewoo' );
		$this->description = __( "Erase stored data for guests that have not placed an order. Orders that are failed or cancelled are not included.", 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function get_form_fields() {
		$fields = [];

		$type = new Fields\Select();
		$type->set_name('type');
		$type->set_name_base('args');

		return $fields;
	}


	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	function process( $args ) {
		$query = new Guest_Query();
		$query->where('most_recent_order', 0 );
		$guests = $query->get_results();

		$tasks = [];

		foreach ( $guests as $guest ) {
			$tasks[] = [
				'tool_id' => $this->get_id(),
				'guest_email' => $guest->get_email(),
			];
		}

		return $this->start_background_job( $tasks );
	}


	/**
	 * Do validation in the validate_process() method not here
	 *
	 * @param $args
	 */
	function display_confirmation_screen( $args ) {
		$query = new Guest_Query();
		$query->where('most_recent_order', 0 );
		$count = $query->get_count();

		$text = __( 'Are you sure you want to permanently delete the guests who have not placed an order? Any workflow logs for deleted guests will be anonymized.', 'automatewoo' );
		$number_string = sprintf( _n( '%s guest will be deleted.', '%s guests will be deleted.', $count, 'automatewoo' ), $count );

		echo '<p>' . $text . ' ' . $number_string . '</p>';
	}


	/**
	 * @param array $task
	 */
	function handle_background_task( $task ) {
		$email = isset( $task['guest_email'] ) ? Clean::email( $task['guest_email'] ) : false;

		if ( ! $email ) {
			return;
		}

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			return;
		}

		// anonymize the guest logs
		$query = new Log_Query();
		$query->where_customer_or_legacy_user( $customer, true );
		$results = $query->get_results();

		foreach( $results as $log ) {
			Privacy_Erasers::anonymize_personal_log_data( $log, $email );
		}

		// delete queued items
		$query = new Queue_Query();
		$query->where_customer_or_legacy_user( $customer, true );
		$results = $query->get_results();

		foreach( $results as $result ) {
			$result->delete();
		}

		if ( $guest = Guest_Factory::get_by_email( $email ) ) {
			$guest->delete(); // delete the guest, cart and customer
		}
	}

}

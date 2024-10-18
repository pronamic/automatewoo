<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Queue_Manager
 */
class Queue_Manager {

	/**
	 * @param $code
	 * @return string
	 */
	static function get_failure_message( $code ) {

		$messages = [
			Queued_Event::F_WORKFLOW_INACTIVE => __( 'The workflow was deleted or deactivated.', 'automatewoo' ),
			Queued_Event::F_MISSING_DATA => __( 'Some of the required data was not found. For example, the order could have been deleted.', 'automatewoo' ),
			Queued_Event::F_FATAL_ERROR => __( 'A fatal error occurred while running the queued event.', 'automatewoo' ),
		];

		if ( isset( $messages[$code] ) ) {
			return $messages[$code];
		}

		return __( 'Cause of queued event failure is unknown.', 'automatewoo' );
	}

	/**
	 * Returns the meta key that a data item is mapped to in queue meta.
	 *
	 * @param $data_type_id string
	 * @return bool|string
	 */
	static function get_data_layer_storage_key( $data_type_id ) {
		return 'data_item_' . $data_type_id;
	}


	/**
	 * @param $data_type_id
	 * @param $data_item : must be validated
	 * @return mixed
	 */
	static function get_data_layer_storage_value( $data_type_id, $data_item ) {
		// same method as logs
		return Logs::get_data_layer_storage_value( $data_type_id, $data_item );
	}

}

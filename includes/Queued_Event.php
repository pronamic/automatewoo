<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Workflows\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * @class Queued_Event
 *
 * @property array $data_items (legacy)
 */
class Queued_Event extends Abstract_Model_With_Meta_Table {

	/** @var string */
	public $table_id = 'queue';

	/** @var string  */
	public $object_type = 'queue';

	/** @var bool|array */
	private $uncompressed_data_layer;


	// error messages
	const F_WORKFLOW_INACTIVE = 100;
	const F_MISSING_DATA      = 101;
	const F_FATAL_ERROR       = 102;

	/**
	 * Returns the ID of the model's meta table.
	 *
	 * @return string
	 */
	public function get_meta_table_id() {
		return 'queue-meta';
	}

	/**
	 * @param bool|int $id
	 */
	public function __construct( $id = false ) {
		if ( $id ) {
			$this->get_by( 'id', $id );
		}
	}


	/**
	 * Set workflow ID prop.
	 *
	 * @param int $id
	 *
	 * @return $this
	 */
	public function set_workflow_id( $id ) {
		$this->set_prop( 'workflow_id', Clean::id( $id ) );
		return $this;
	}


	/**
	 * @return int
	 */
	public function get_workflow_id() {
		return Clean::id( $this->get_prop( 'workflow_id' ) );
	}


	/**
	 * Set failed prop.
	 *
	 * @param bool $failed
	 *
	 * @return $this
	 */
	public function set_failed( $failed = true ) {
		$this->set_prop( 'failed', aw_bool_int( $failed ) );
		return $this;
	}


	/**
	 * @return bool
	 */
	public function is_failed() {
		return (bool) $this->get_prop( 'failed' );
	}


	/**
	 * @param int $failure_code
	 *
	 * @return $this
	 */
	public function set_failure_code( $failure_code ) {
		$this->set_prop( 'failure_code', absint( $failure_code ) );
		return $this;
	}

	/**
	 * @return int
	 */
	public function get_failure_code() {
		return absint( $this->get_prop( 'failure_code' ) );
	}


	/**
	 * @param DateTime $date
	 *
	 * @return $this
	 */
	public function set_date_created( $date ) {
		$this->set_date_column( 'created', $date );
		return $this;
	}


	/**
	 * @return bool|DateTime
	 */
	public function get_date_created() {
		return $this->get_date_column( 'created' );
	}


	/**
	 * @param DateTime $date
	 *
	 * @return $this
	 */
	public function set_date_due( $date ) {
		$this->set_date_column( 'date', $date );
		return $this;
	}


	/**
	 * @return bool|DateTime
	 */
	public function get_date_due() {
		return $this->get_date_column( 'date' );
	}


	/**
	 * @param Data_Layer $data_layer The data layer for the queued event
	 */
	public function store_data_layer( $data_layer ) {
		$this->uncompressed_data_layer = $data_layer->get_raw_data();

		foreach ( $this->uncompressed_data_layer as $data_type_id => $data_item ) {
			$this->store_data_item( $data_type_id, $data_item );
		}
	}

	/**
	 * @param string $data_type_id Data type object ID
	 * @param mixed  $data_item    The data item
	 */
	private function store_data_item( $data_type_id, $data_item ) {
		$data_type = DataTypes::get( $data_type_id );

		if (
			! $data_type ||
			! $data_type->validate( $data_item ) ||
			DataTypes::is_non_stored_data_type( $data_type_id )
		) {
			return;
		}

		$storage_key   = Queue_Manager::get_data_layer_storage_key( $data_type_id );
		$storage_value = $data_type->compress( $data_item );

		if ( $storage_key ) {
			$this->update_meta( $storage_key, $storage_value );
		}
	}


	/**
	 * @return Data_Layer The data layer instance
	 */
	public function get_data_layer() {
		if ( ! isset( $this->uncompressed_data_layer ) ) {

			$uncompressed_data_layer = [];
			$compressed_data_layer   = $this->get_compressed_data_layer();

			if ( $compressed_data_layer ) {
				foreach ( $compressed_data_layer as $data_type_id => $compressed_item ) {
					$data_type = DataTypes::get( $data_type_id );
					if ( $data_type ) {
						$decompressed_data = $data_type->decompress( $compressed_item, $compressed_data_layer );
						if ( ! $decompressed_data && $this->id ) {
							$message = sprintf(
								'Some of the required data was not found. Queued event ID: %s. The missing "%s" data\'s ID: %d.',
								$this->id,
								$data_type_id,
								Clean::id( $compressed_item )
							);
							if ( 'subscription' === $data_type_id ) {
								$message .= ' For example, the order or subscription order could have been deleted. This issue may occur when a subscription order initially fails due to payment issues, but later succeeds. In such cases, the subscription order with the failed payment is deleted, but its workflow remains intact.';
							}
							Logger::info( 'queued-event', $message );
						}
						$uncompressed_data_layer[ $data_type_id ] = $decompressed_data;
					}
				}
			}

			$this->uncompressed_data_layer = new Data_Layer( $uncompressed_data_layer );
		}

		return $this->uncompressed_data_layer;
	}


	/**
	 * Fetches the data layer from queue meta, but does not decompress
	 * Uses the the supplied_data_items field on the workflows trigger
	 *
	 * @return array|false
	 */
	public function get_compressed_data_layer() {
		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false; // workflow must be set
		}

		if ( ! $this->exists ) {
			return false; // queue must be saved
		}

		$trigger = $workflow->get_trigger();
		if ( ! $trigger ) {
			return false; // need a trigger
		}

		$data_layer = [];

		$supplied_items = $trigger->get_supplied_data_items();

		foreach ( $supplied_items as $data_type_id ) {
			$data_item_value = $this->get_compressed_data_item( $data_type_id, $supplied_items );

			if ( $data_item_value !== false ) {
				$data_layer[ $data_type_id ] = $data_item_value;
			}
		}

		return $data_layer;
	}


	/**
	 * @param string $data_type_id        Data type object ID
	 * @param array  $supplied_data_items An array of supplied data items
	 *
	 * @return string|false
	 */
	private function get_compressed_data_item( $data_type_id, $supplied_data_items ) {
		if ( DataTypes::is_non_stored_data_type( $data_type_id ) ) {
			return false; // storage not required
		}

		$storage_key = Queue_Manager::get_data_layer_storage_key( $data_type_id );

		if ( ! $storage_key ) {
			return false;
		}

		return Clean::string( $this->get_meta( $storage_key ) );
	}


	/**
	 * Returns the workflow without a data layer
	 *
	 * @return Workflow|false
	 */
	public function get_workflow() {
		return Factory::get( $this->get_workflow_id() );
	}


	/**
	 * @return bool
	 */
	public function run() {
		if ( ! $this->exists ) {
			return false;
		}

		// mark as failed and then delete if complete, so fatal error will not cause it to run repeatedly
		$this->mark_as_failed( self::F_FATAL_ERROR );
		$this->save();
		$success = false;

		$workflow = $this->get_workflow();
		$workflow->setup( $this->get_data_layer() );

		$failure = $this->do_failure_check( $workflow );

		if ( $failure ) {
			// queued event failed
			$this->mark_as_failed( $failure );
		} else {
			$success = true;

			// passed fail check so validate workflow and then delete
			if ( $this->validate_workflow( $workflow ) ) {
				$workflow->run();
			} else {
				// Order is no longer valid for this workflow so add a log before deleting the queued entry
				$workflow->create_run_log();

				$log = $workflow->get_current_log();

				$log->add_note( __( 'Queued Workflow: Run failed as requirements are no longer met for this workflow.', 'automatewoo' ) );
				$log->set_has_errors( true );
				$log->save();
			}

			$this->delete();
		}

		// important to always clean up
		$workflow->cleanup();
		return $success;
	}


	/**
	 * Returns false if no failure occurred
	 *
	 * @param Workflow $workflow
	 * @return bool|int
	 */
	public function do_failure_check( $workflow ) {
		if ( ! $workflow || ! $workflow->is_active() ) {
			return self::F_WORKFLOW_INACTIVE;
		}

		if ( $this->get_data_layer()->is_missing_data() ) {
			return self::F_MISSING_DATA;
		}

		return false;
	}


	/**
	 * Validate the workflow before running it from the queue.
	 * This validation is different from the initial trigger validation.
	 *
	 * @param Workflow $workflow The Workflow to validate
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$trigger = $workflow->get_trigger();
		if ( ! $trigger ) {
			return false;
		}

		if ( ! $trigger->validate_before_queued_event( $workflow ) ) {
			return false;
		}

		if ( ! $workflow->validate_rules() ) {
			return false;
		}

		return true;
	}

	/**
	 * Clear cached workflow data stored as a transient `current_queue_count/workflow=WORKFLOW_ID`
	 *
	 * @return void
	 */
	public function clear_cached_data() {
		if ( ! $this->get_workflow_id() ) {
			return;
		}

		Cache::delete_transient( 'current_queue_count/workflow=' . $this->get_workflow_id() );
	}

	/**
	 * Save the current queued event
	 *
	 * @return void
	 */
	public function save() {
		if ( ! $this->exists ) {
			$this->set_date_created( new DateTime() );
		}

		$this->clear_cached_data();

		parent::save();
	}

	/**
	 * Delete the current queued event
	 *
	 * @return void
	 */
	public function delete() {
		$this->clear_cached_data();
		parent::delete();
	}

	/**
	 * @param int $code The error code to set for the current queued event
	 */
	public function mark_as_failed( $code ) {
		$this->set_failed();
		$this->set_failure_code( $code );
		$this->save();
	}

	/**
	 * Get failure message from failure code
	 *
	 * @return string
	 */
	public function get_failure_message() {
		return Queue_Manager::get_failure_message( $this->get_failure_code() );
	}


	/**
	 * Just for unit tests
	 */
	public function clear_in_memory_data_layer() {
		$this->uncompressed_data_layer = null;
	}
}

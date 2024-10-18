<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Log
 */
class Log extends Abstract_Model_With_Meta_Table {

	/** @var string */
	public $table_id = 'logs';

	/** @var string  */
	public $object_type = 'log';

	/** @var Data_Layer */
	private $data_layer;

	/**
	 * Returns the ID of the model's meta table.
	 *
	 * @return string
	 */
	public function get_meta_table_id() {
		return 'log-meta';
	}

	/**
	 * @param bool|int $id
	 */
	function __construct( $id = false ) {
		if ( $id ) $this->get_by( 'id', $id );
	}


	/**
	 * @param int $workflow_id
	 */
	function set_workflow_id( $workflow_id ) {
		$this->set_prop( 'workflow_id', Clean::id( $workflow_id ) );
	}


	/**
	 * @return int
	 */
	function get_workflow_id() {
		return Clean::id( $this->get_prop( 'workflow_id' ) );
	}


	/**
	 * @param DateTime $date
	 */
	function set_date( $date ) {
		$this->set_date_column( 'date', $date );
	}


	/**
	 * @return DateTime|bool
	 */
	function get_date() {
		return $this->get_date_column( 'date' );
	}


	/**
	 * @param bool $has_errors
	 */
	function set_has_errors( $has_errors ) {
		$this->set_prop( 'has_errors', aw_bool_int( $has_errors ) );
	}


	/**
	 * @return bool
	 */
	function has_errors() {
		return (bool) $this->get_prop( 'has_errors' );
	}


	/**
	 * @param bool $has_blocked_emails
	 */
	function set_has_blocked_emails( $has_blocked_emails ) {
		$this->set_prop( 'has_blocked_emails', absint( (bool) $has_blocked_emails ) );
	}


	/**
	 * @return bool
	 */
	function has_blocked_emails() {
		return (bool) $this->get_prop( 'has_blocked_emails' );
	}


	/**
	 * @param bool $enabled
	 */
	function set_tracking_enabled( $enabled ) {
		$this->set_prop( 'tracking_enabled', aw_bool_int( $enabled ) );
	}


	/**
	 * @return bool
	 */
	function is_tracking_enabled() {
		return (bool) $this->get_prop( 'tracking_enabled' );
	}


	/**
	 * @param bool $enabled
	 */
	function set_conversion_tracking_enabled( $enabled ) {
		$this->set_prop( 'conversion_tracking_enabled', aw_bool_int( $enabled ) );
	}


	/**
	 * @return bool
	 */
	function is_conversion_tracking_enabled() {
		return (bool) $this->get_prop( 'conversion_tracking_enabled' );
	}


	/**
	 * @return bool
	 */
	function is_anonymized() {
		return (bool) $this->get_meta( 'is_anonymized' );
	}


	/**
	 * Records that a trackable message from a workflow was clicked.
	 *
	 * @param string $url
	 */
	public function record_click( $url ) {

		if ( ! $tracking = $this->get_meta('tracking_data') ) {
			$tracking = [];
		}

		$tracking[] = [
			'type' => 'click',
			'url' => $url,
			'date' => current_time( 'mysql', true )
		];

		// clicking requires an open so record one in case images were blocked
		if ( ! $this->has_open_recorded() ) {
			$tracking[] = [
				'type' => 'open',
				'date' => current_time( 'mysql', true )
			];
		}

		$this->update_meta( 'tracking_data', $tracking );

		/**
		 * Fires when a link in a trackable workflow message is clicked.
		 *
		 * @param Workflow $workflow The workflow that sent the message.
		 * @param Log      $log      The record of the workflow running.
		 * @param string   $url      The URL that was clicked
		 */
		do_action( 'automatewoo/workflow/record_click', $this->get_workflow(), $this, $url );
	}


	/**
	 * Records that a trackable message from a workflow was opened.
	 *
	 * Only records an open once i.e. unique opens.
	 */
	public function record_open() {

		if ( $this->has_open_recorded() ) {
			return; // already opened
		}

		if ( ! $tracking = $this->get_meta('tracking_data') ) {
			$tracking = [];
		}

		$tracking[] = [
			'type' => 'open',
			'date' => current_time( 'mysql', true )
		];

		$this->update_meta( 'tracking_data', $tracking );

		/**
		 * Fires for unique opens of a trackable workflow message.
		 *
		 * @param Workflow $workflow The workflow that sent the message.
		 * @param Log      $log      The record of the workflow running.
		 */
		do_action( 'automatewoo/workflow/record_open', $this->get_workflow(), $this );
	}


	/**
	 * @return bool
	 */
	function has_open_recorded() {

		$tracking = $this->get_meta('tracking_data');

		if ( is_array( $tracking ) ) foreach( $tracking as $item ) {
			if ( $item['type'] == 'open') {
				return true;
			}
		}

		return false;
	}


	/**
	 * @return bool
	 */
	function has_click_recorded() {

		$tracking = $this->get_meta('tracking_data');

		if ( is_array( $tracking ) ) foreach( $tracking as $item ) {
			if ( $item['type'] == 'click')
				return true;
		}

		return false;
	}


	/**
	 * @return DateTime|false
	 */
	function get_date_opened() {
		$tracking = $this->get_meta('tracking_data');

		if ( is_array( $tracking ) ) foreach( $tracking as $item ) {
			if ( $item['type'] == 'open') {
				return new DateTime( $item['date'] );
			}
		}

		return false;
	}


	/**
	 * @return DateTime|false
	 */
	function get_date_clicked() {
		$tracking = $this->get_meta('tracking_data');

		if ( is_array( $tracking ) ) foreach( $tracking as $item ) {
			if ( $item['type'] == 'click') {
				return new DateTime( $item['date'] );
			}
		}

		return false;
	}

	/**
	 * Add a note.
	 *
	 * @param string $note
	 */
	function add_note( $note ) {
		$notes = $this->get_notes();
		$notes[] = $note;
		$this->update_notes( $notes );
	}

	/**
	 * Get the log notes.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	function get_notes() {
		$notes = $this->get_meta( 'notes' );
		if ( ! is_array( $notes ) ) {
			return [];
		}
		else {
			return Clean::recursive( $notes );
		}
	}

	/**
	 * Update the log notes.
	 *
	 * @since 4.4.0
	 *
	 * @param array $notes
	 */
	function update_notes( $notes ) {
		$notes = Clean::recursive( $notes );
		$this->update_meta( 'notes', $notes );
	}


	/**
	 * Returns the workflow without a data layer
	 * @return Workflow
	 */
	function get_workflow() {
		return Factory::get( $this->get_workflow_id() );
	}


	/**
	 * @param string $output - array|object this for backwards compatibility
	 * @return Data_Layer|array
	 */
	function get_data_layer( $output = 'array' ) {

		if ( ! isset( $this->data_layer ) ) {
			if ( $compressed = $this->get_compressed_data_layer() ) {
				$this->data_layer = $this->decompress_data_layer( $compressed );
			}
			else {
				$this->data_layer = new Data_Layer();
			}
		}

		if ( $output == 'array' ) {
			return $this->data_layer->get_raw_data();
		}

		return $this->data_layer;
	}


	/**
	 * Fetches the data layer from log meta, but does not decompress
	 * Uses the the supplied_data_items field on the workflows trigger
	 *
	 * @return array|false
	 */
	private function get_compressed_data_layer() {

		if ( ! $workflow = $this->get_workflow() )
			return false; // workflow must be set

		if ( ! $this->exists )
			return false; // log must be saved

		if ( ! $trigger = $workflow->get_trigger() )
			return false; // need a trigger

		$data_layer = [];
		$supplied_items = $trigger->get_supplied_data_items();

		// when anonymized log is converted to guest
		if ( $this->is_anonymized() ) {
			$supplied_items[] = 'guest';
		}

		foreach ( $supplied_items as $data_type_id ) {

			$data_item_value = $this->get_compressed_data_item( $data_type_id, $trigger->get_supplied_data_items() );

			if ( $data_item_value !== false ) {
				$data_layer[ $data_type_id ] = $data_item_value;
			}
		}

		return $data_layer;
	}


	/**
	 * @param $data_type_id
	 * @param array $supplied_data_items
	 * @return string|false
	 */
	private function get_compressed_data_item( $data_type_id, $supplied_data_items ) {
		if ( DataTypes::is_non_stored_data_type( $data_type_id ) ) {
			return false; // storage not required
		}

		// user requires special logic when related to an order
		if ( $data_type_id === 'user' && in_array( 'order', $supplied_data_items ) ) {
			return 0; // get user data from the order when decompressing
		}

		$storage_key = Logs::get_data_layer_storage_key( $data_type_id );

		if ( ! $storage_key )
			return false;

		return Clean::string( $this->get_meta( $storage_key ) );
	}


	/**
	 * @param array $compressed_data_layer
	 * @return Data_Layer
	 */
	private function decompress_data_layer( $compressed_data_layer ) {

		$data = [];

		if ( is_array( $compressed_data_layer ) ) foreach ( $compressed_data_layer as $data_type_id => $compressed_item ) {
			if ( $data_type = DataTypes::get( $data_type_id ) ) {
				$data[$data_type_id] = $data_type->decompress( $compressed_item, $compressed_data_layer );
			}
		}

		return new Data_Layer( $data );
	}


	/**
	 * Stores a data layer in log meta
	 * @param Data_Layer $data_layer
	 */
	function store_data_layer( $data_layer ) {

		if ( ! $this->exists )
			return; // log must be saved before meta can be added

		foreach ( $data_layer->get_raw_data() as $data_type_id => $data_item ) {
			$this->store_data_item( $data_type_id, $data_item );
		}
	}


	/**
	 * @param $data_type_id
	 * @param $data_item
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

		// special logic for users who are actually guests
		if ( $data_type_id === 'user' && $data_item->ID === 0 ) {
			$storage_key = 'guest_email';
			$storage_value = $data_item->user_email;
		}
		else {
			$storage_key = Logs::get_data_layer_storage_key( $data_type_id );
			$storage_value = Logs::get_data_layer_storage_value( $data_type_id, $data_item );
		}

		if ( $storage_key ) {
			$this->update_meta( $storage_key, $storage_value );
		}
	}


	/**
	 * Delete the log and clear related conversion order meta
	 */
	function delete() {

		// Get orders with conversion data.
		$converted_orders = wc_get_orders(
			[
				'type'       => 'shop_order',
				'status'     => 'any',
				'limit'      => -1,
				'meta_key'   => '_aw_conversion_log',
				'meta_value' => $this->get_id(),
			]
		);

		if ( $converted_orders ) {
			foreach ( $converted_orders as $order ) {
				$order->delete_meta_data( '_aw_conversion' );
				$order->delete_meta_data( '_aw_conversion_log' );
				$order->save();
			}
		}

		$this->clear_cached_data();
		parent::delete();
	}


	/**
	 *
	 */
	function save() {
		$this->clear_cached_data();
		parent::save();
	}


	function clear_cached_data() {

		if ( ! $this->get_workflow_id() )
			return;

		Cache::delete_transient( 'times_run/workflow=' . $this->get_workflow_id() );
	}


	/**
	 * Reruns the workflow skipping validation
	 * @return Log|bool - the newly created log
	 */
	function rerun() {
		$workflow = $this->get_workflow();
		$workflow->maybe_run( $this->get_data_layer( 'object' ), true, true );

		$log = $workflow->get_current_log();

		if ( $log ) {
			$log->add_note( __( 'This log was created from manual workflow re-run.', 'automatewoo' ) );
			return $log;
		}

		return false;
	}

}


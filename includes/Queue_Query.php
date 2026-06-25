<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Queue_Query
 * @since 2.1.0
 */
class Queue_Query extends Query_Data_Layer_Abstract {

	/** @var string */
	public $table_id = 'queue';

	/** @var string  */
	protected $model = 'AutomateWoo\Queued_Event';

	/** @var string  */
	public $meta_table_id = 'queue-meta';


	/**
	 * @since 3.8
	 * @param int|array   $workflow_id
	 * @param bool|string $compare defaults to '=' or 'IN' if array
	 * @return $this
	 */
	public function where_workflow( $workflow_id, $compare = false ) {
		return $this->where( 'workflow_id', $workflow_id, $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param bool|string     $compare defaults to '=' or 'IN' if array
	 * @return $this
	 */
	public function where_date_due( $date, $compare = false ) {
		return $this->where( 'date', $date, $compare );
	}


	/**
	 * @since 3.8
	 * @param bool        $failed
	 * @param bool|string $compare defaults to '=' or 'IN' if array
	 * @return $this
	 */
	public function where_failed( $failed, $compare = false ) {
		return $this->where( 'failed', absint( $failed ), $compare );
	}

	/**
	 * Order non-failed events before failed events, then by due date.
	 *
	 * @since 6.5.0
	 * @return $this
	 */
	public function order_by_failed_status_and_date() {
		$this->orderby = 'failed ASC, date';
		$this->order   = 'ASC';

		return $this;
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param bool|string     $compare defaults to '=' or 'IN' if array
	 * @return $this
	 */
	public function where_date_created( $date, $compare = false ) {
		return $this->where( 'created', $date, $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $start_date
	 * @param string|DateTime $end_date
	 * @return $this
	 */
	public function where_date_created_between( $start_date, $end_date ) {
		$this->where_date_created( $start_date, '>' );
		return $this->where_date_created( $end_date, '<' );
	}

	/**
	 * @since 3.8
	 * @param string $data_type
	 * @return string
	 */
	public function get_data_layer_meta_key( $data_type ) {
		return Queue_Manager::get_data_layer_storage_key( $data_type );
	}


	/**
	 * @since 3.8
	 * @param string $data_type
	 * @param mixed  $data_object
	 * @return string
	 */
	public function get_data_layer_meta_value( $data_type, $data_object ) {
		return Queue_Manager::get_data_layer_storage_value( $data_type, $data_object );
	}


	/**
	 * @return Queued_Event[]
	 */
	public function get_results() {
		return parent::get_results();
	}
}

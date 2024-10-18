<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

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
	 * @param int|array $workflow_id
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_workflow( $workflow_id, $compare = false ) {
		return $this->where( 'workflow_id', $workflow_id, $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_date_due( $date, $compare = false ) {
		return $this->where( 'date', $date, $compare );
	}


	/**
	 * @since 3.8
	 * @param bool $failed
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_failed( $failed, $compare = false ) {
		return $this->where( 'failed', absint( $failed ), $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_date_created( $date, $compare = false ) {
		return $this->where( 'created', $date, $compare );
	}


	/**
	 * @since 3.8
	 * @param $start_date
	 * @param $end_date
	 * @return $this
	 */
	function where_date_created_between( $start_date, $end_date ) {
		$this->where_date_created( $start_date, '>' );
		return $this->where_date_created( $end_date, '<' );
	}


	/**
	 * @since 4.0
	 * @param Customer $customer
	 * @param bool $include_guest_matches include matching guest results
	 * @param bool $include_advocate_matches
	 * @return $this
	 */
	function where_customer_or_legacy_user( $customer, $include_guest_matches = false, $include_advocate_matches = false ) {
		$where_meta = [];

		$where_meta[] = [
			'key' => $this->get_data_layer_meta_key( 'customer' ),
			'value' => $customer->get_id()
		];

		if ( $customer->is_registered() ) {
			$where_meta[] = [
				'key' => $this->get_data_layer_meta_key( 'user' ),
				'value' => $customer->get_user_id()
			];

			if ( $include_advocate_matches ) {
				$where_meta[] = [
					'key' => $this->get_data_layer_meta_key( 'advocate' ),
					'value' => $customer->get_user_id()
				];
			}
		}

		if ( $include_guest_matches ) {
			$where_meta[] = [
				'key' => $this->get_data_layer_meta_key( 'guest' ),
				'value' => $customer->get_email()
			];
		}

		$this->where_meta[] = $where_meta;
		return $this;
	}


	/**
	 * @since 3.8
	 * @param string $data_type
	 * @return string
	 */
	function get_data_layer_meta_key( $data_type ) {
		return Queue_Manager::get_data_layer_storage_key( $data_type );
	}


	/**
	 * @since 3.8
	 * @param string $data_type
	 * @param mixed $data_object
	 * @return string
	 */
	function get_data_layer_meta_value( $data_type, $data_object ) {
		return Queue_Manager::get_data_layer_storage_value( $data_type, $data_object );
	}


	/**
	 * @return Queued_Event[]
	 */
	function get_results() {
		return parent::get_results();
	}

}

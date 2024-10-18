<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Log_Query
 */
class Log_Query extends Query_Data_Layer_Abstract {

	/** @var string */
	public $table_id = 'logs';

	/** @var string  */
	public $meta_table_id = 'log-meta';

	/** @var string */
	public $model = 'AutomateWoo\Log';


	/**
	 * @since 3.8
	 *
	 * @param int|array|Workflow $workflow Workflow object, ID or array of IDs.
	 * @param string             $compare  Defaults to '=' or 'IN' if $workflow is array.
	 *
	 * @return $this
	 */
	function where_workflow( $workflow, $compare = null ) {
		$workflow = is_a( $workflow, 'AutomateWoo\Workflow' ) ? $workflow->get_id() : $workflow;
		return $this->where( 'workflow_id', $workflow, $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_date( $date, $compare = false ) {
		return $this->where( 'date', $date, $compare );
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
	 * @param $start_date
	 * @param $end_date
	 * @return $this
	 */
	function where_date_between( $start_date, $end_date ) {
		$this->where_date( $start_date, '>' );
		$this->where_date( $end_date, '<' );
		return $this;
	}


	/**
	 * @since 3.8
	 * @param string $data_type
	 * @return string
	 */
	function get_data_layer_meta_key( $data_type ) {
		return Logs::get_data_layer_storage_key( $data_type );
	}


	/**
	 * @since 3.8
	 * @param string $data_type
	 * @param mixed $data_object
	 * @return string
	 */
	function get_data_layer_meta_value( $data_type, $data_object ) {
		return Logs::get_data_layer_storage_value( $data_type, $data_object );
	}
	

	/**
	 * @return Log[]
	 */
	function get_results() {
		return parent::get_results();
	}

}

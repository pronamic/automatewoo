<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Cart_Query
 * @since 2.0
 */
class Cart_Query extends Query_Abstract {

	/** @var string */
	public $table_id = 'carts';

	protected $model = 'AutomateWoo\Cart';


	/**
	 * @since 3.8
	 * @param string|array $status active, abandoned
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_status( $status, $compare = false ) {
		return $this->where( 'status', $status, $compare );
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
	 * @param string|DateTime $start_date
	 * @param string|DateTime $end_date
	 * @return $this
	 */
	function where_date_created_between( $start_date, $end_date ) {
		$this->where_date_created( $start_date, '>' );
		return $this->where_date_created( $end_date, '<' );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $date
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_date_modified( $date, $compare = false ) {
		return $this->where( 'last_modified', $date, $compare );
	}


	/**
	 * @since 3.8
	 * @param string|DateTime $start_date
	 * @param string|DateTime $end_date
	 * @return $this
	 */
	function where_date_modified_between( $start_date, $end_date ) {
		$this->where_date_modified( $start_date, '>' );
		return $this->where_date_modified( $end_date, '<' );
	}


	/**
	 * Filter results by user or guest ID.
	 *
	 * @since 5.2.0
	 * @param Customer $customer
	 * @return $this
	 */
	public function where_customer( Customer $customer ) {
		if ( $customer->is_registered() ) {
			return $this->where( 'user_id', $customer->get_user_id() );
		}

		return $this->where( 'guest_id', $customer->get_guest_id() );
	}


	/**
	 * @return Cart[]
	 */
	function get_results() {
		return parent::get_results();
	}

}

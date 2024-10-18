<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guest_Query
 * @since 2.0
 */
class Guest_Query extends Query_Abstract {

	/** @var string */
	public $table_id = 'guests';

	/** @var string  */
	public $meta_table_id = 'guest-meta';

	/** @var string  */
	protected $model = 'AutomateWoo\Guest';


	/**
	 * @since 4.1
	 * @param int|array $order_id
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_most_recent_order( $order_id, $compare = false ) {
		return $this->where( 'most_recent_order', $order_id, $compare );
	}


	/**
	 * @since 4.2
	 * @param string $version
	 * @param $compare bool|string - defaults to '='
	 * @return $this
	 */
	function where_version( $version, $compare = false ) {
		return $this->where( 'version', aw_version_str_to_int( $version ), $compare );
	}


	/**
	 * @return Guest[]
	 */
	function get_results() {
		return parent::get_results();
	}

}

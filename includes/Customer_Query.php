<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Customer_Query
 * @since 3.0.0
 */
class Customer_Query extends Query_Abstract {

	/** @var string */
	public $table_id = 'customers';

	protected $model = 'AutomateWoo\Customer';


	/**
	 * @return Customer[]
	 */
	function get_results() {
		return parent::get_results();
	}

}

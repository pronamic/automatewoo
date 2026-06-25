<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Customer_Query
 * @since 3.0.0
 */
class Customer_Query extends Query_Abstract {

	/** @var string */
	public $table_id = 'customers';

	/** @var string */
	public $meta_table_id = 'customer-meta';

	/** @var string */
	protected $model = 'AutomateWoo\Customer';


	/**
	 * @return Customer[]
	 */
	public function get_results() {
		return parent::get_results();
	}
}

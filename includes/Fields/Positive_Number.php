<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Number field that only allows positive values (>0)
 *
 * @class Positive_Number
 * @since 4.9.4
 * @package AutomateWoo\Fields
 */
class Positive_Number extends Number {

	/**
	 * Positive_Number constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_min( 1 );
	}
}

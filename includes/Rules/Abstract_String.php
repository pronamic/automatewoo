<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_String
 */
abstract class Abstract_String extends Rule {

	public $type = 'string';

	function __construct() {
		$this->compare_types = $this->get_string_compare_types();
		parent::__construct();
	}

}

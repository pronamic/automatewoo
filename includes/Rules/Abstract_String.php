<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_String
 */
abstract class Abstract_String extends Rule {

	/** @var string */
	public $type = 'string';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->compare_types = $this->get_string_compare_types();
		parent::__construct();
	}
}

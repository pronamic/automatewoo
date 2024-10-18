<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Bool
 */
abstract class Abstract_Bool extends Rule {

	public $type = 'bool';

	public $select_choices;

	function __construct() {

		$this->select_choices = [
			'yes' => __( 'Yes', 'automatewoo' ),
			'no' => __( 'No','automatewoo' )
		];

		parent::__construct();
	}

}
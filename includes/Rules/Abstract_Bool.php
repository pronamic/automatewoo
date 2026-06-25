<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Bool
 */
abstract class Abstract_Bool extends Rule {

	/** @var string */
	public $type = 'bool';

	/** @var array */
	public $select_choices;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->select_choices = [
			'yes' => __( 'Yes', 'automatewoo' ),
			'no'  => __( 'No', 'automatewoo' ),
		];

		parent::__construct();
	}
}

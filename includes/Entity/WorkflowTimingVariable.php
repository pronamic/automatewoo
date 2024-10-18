<?php

namespace AutomateWoo\Entity;

/**
 * @class WorkflowTimingVariable
 * @since 5.1.0
 */
class WorkflowTimingVariable extends WorkflowTimingBase {

	const TYPE = 'datetime';

	/**
	 * @var string
	 */
	protected $variable;

	/**
	 * @param string $variable
	 */
	public function __construct( $variable ) {
		$this->variable = $variable;
	}

	/**
	 * @return string
	 */
	public function get_variable() {
		return $this->variable;
	}

	/**
	 * @param string $variable
	 * @return $this
	 */
	public function set_variable( $variable ) {
		$this->variable = $variable;
		return $this;
	}
}

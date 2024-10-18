<?php

namespace AutomateWoo\Workflows\VariableParsing;

/**
 * Class ExcludedVariable
 *
 * @since 5.4.0
 */
class ExcludedParsedVariable {

	/**
	 * E.g. 'unsubscribe_url'.
	 *
	 * @var string
	 */
	public $variable_string;

	/**
	 * ExcludedParsedVariable constructor.
	 *
	 * @param string $variable_string
	 */
	public function __construct( string $variable_string ) {
		$this->variable_string = $variable_string;
	}
}

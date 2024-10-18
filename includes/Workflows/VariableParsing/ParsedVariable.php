<?php

namespace AutomateWoo\Workflows\VariableParsing;

/**
 * Class ParsedVariable
 *
 * The result of a parsed variable string.
 *
 * @since 5.4.0
 */
class ParsedVariable {

	/**
	 * E.g. 'order.total'.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * E.g. 'order'.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * E.g. 'total'.
	 *
	 * @var string
	 */
	public $field;

	/**
	 * E.g. [ 'format' => 'decimal' ]
	 *
	 * @var array
	 */
	public $parameters;

	/**
	 * ParsedVariable constructor.
	 *
	 * @param string $name
	 * @param string $type
	 * @param string $field
	 * @param array  $parameters
	 */
	public function __construct( string $name, string $type, string $field, array $parameters ) {
		$this->name       = $name;
		$this->type       = $type;
		$this->field      = $field;
		$this->parameters = $parameters;
	}
}

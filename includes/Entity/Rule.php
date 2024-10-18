<?php

namespace AutomateWoo\Entity;

use AutomateWoo\Traits\NamedEntity;

/**
 * @class Rule
 * @since 5.1.0
 */
class Rule implements ToArray {

	use NamedEntity;

	/**
	 * @var string|null
	 */
	protected $compare;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * Rule constructor.
	 *
	 * @param string      $name
	 * @param string|null $compare
	 * @param mixed       $value
	 */
	public function __construct( $name, $compare = null, $value = null ) {
		$this->name    = $name;
		$this->compare = $compare;
		$this->value   = $value;
	}

	/**
	 * @return string|null
	 */
	public function get_compare() {
		return $this->compare;
	}

	/**
	 * @param string|null $compare
	 *
	 * @return $this
	 */
	public function set_compare( $compare ) {
		$this->compare = $compare;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function set_value( $value ) {
		$this->value = $value;

		return $this;
	}

	/**
	 * Convert the object's data to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'name'    => $this->get_name() ?? '',
			'compare' => $this->get_compare(),
			'value'   => $this->get_value(),
		];
	}
}

<?php

namespace AutomateWoo\Traits;

/**
 * NamedEntity trait.
 *
 * @since   5.1.0
 * @package AutomateWoo\Traits
 */
trait NamedEntity {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function set_name( $name ) {
		$this->name = $name;

		return $this;
	}
}

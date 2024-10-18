<?php

namespace AutomateWoo\Traits;

/**
 * OptionsEntity trait.
 *
 * @since   5.1.0
 * @package AutomateWoo\Traits
 */
trait OptionsEntity {

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get_option( $key, $default = null ) {
		return $this->options[ $key ] ?? $default;
	}

	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function set_options( $options ) {
		$this->options = $options;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	public function set_option( $key, $value ) {
		$this->options[ $key ] = $value;

		return $this;
	}
}

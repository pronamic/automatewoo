<?php

namespace AutomateWoo\Entity;

use AutomateWoo\Traits\NamedEntity;
use AutomateWoo\Traits\OptionsEntity;

/**
 * NamedEntity class.
 *
 * @since   5.1.0
 * @package AutomateWoo\Entity
 */
abstract class NamedEntityWithOptions implements ToArray {

	use NamedEntity;
	use OptionsEntity;

	/**
	 * NamedEntityWithOptions constructor.
	 *
	 * @param string $name    The entity name.
	 * @param array  $options Options for the entity.
	 */
	public function __construct( $name, $options = [] ) {
		$this->name    = $name;
		$this->options = $options;
	}

	/**
	 * Convert the object's data to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'name'    => $this->get_name() ?? '',
			'options' => $this->get_options() ?? [],
		];
	}
}

<?php

namespace AutomateWoo\Entity;

/**
 * ToArray interface.
 *
 * @since   5.1.0
 * @package AutomateWoo\Entity
 */
interface ToArray {

	/**
	 * Convert the object's data to an array.
	 *
	 * @return array
	 */
	public function to_array(): array;
}

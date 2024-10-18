<?php

namespace AutomateWoo\Jobs\Traits;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\IntegerValidator;

/**
 * Trait ValidateItemAsIntegerId
 *
 * @since 5.1.0
 */
trait ValidateItemAsIntegerId {

	use IntegerValidator;

	/**
	 * Validate an item.
	 *
	 * @param mixed $item
	 *
	 * @throws InvalidArgument If the item is not valid.
	 */
	protected function validate_item( $item ) {
		$this->validate_positive_integer( $item );
	}
}

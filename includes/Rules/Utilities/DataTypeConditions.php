<?php

namespace AutomateWoo\Rules\Utilities;

/**
 * Trait DataTypeConditions
 *
 * @since   5.0.0
 * @package AutomateWoo\Rules\Utilities
 */
trait DataTypeConditions {

	/**
	 * Is a data type equal to 'order' or 'subscription'.
	 *
	 * @param string $data_type
	 *
	 * @return bool
	 */
	protected function is_data_type_order_or_subscription( $data_type ) {
		return 'order' === $data_type || 'subscription' === $data_type;
	}
}

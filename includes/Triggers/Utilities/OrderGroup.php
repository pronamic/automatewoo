<?php

namespace AutomateWoo\Triggers\Utilities;

/**
 * Trait OrderGroup
 *
 * Sets the trigger in the 'Orders' group.
 *
 * @since 5.2.0
 */
trait OrderGroup {

	/**
	 * @return string
	 */
	public function get_group() {
		return __( 'Orders', 'automatewoo' );
	}
}

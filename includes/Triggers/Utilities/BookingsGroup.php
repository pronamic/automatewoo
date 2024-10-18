<?php

namespace AutomateWoo\Triggers\Utilities;

/**
 * Trait BookingsGroup
 *
 * Declare trigger as belonging to Bookings group.
 *
 * @since 5.3.0
 */
trait BookingsGroup {

	/**
	 * @return string
	 */
	public function get_group() {
		return __( 'Bookings', 'automatewoo' );
	}
}

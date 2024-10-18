<?php

namespace AutomateWoo\Jobs\Traits;

use AutomateWoo\DateTime;

/**
 * Trait ItemDeletionDate.
 *
 * @since   5.0.0
 * @package AutomateWoo\Jobs
 */
trait ItemDeletionDate {

	/**
	 * Get the number of days after which the item should be deleted.
	 *
	 * @return int
	 */
	abstract public function get_deletion_period();

	/**
	 * Get the deletion date.
	 *
	 * @return DateTime|false
	 */
	protected function get_deletion_date() {
		return aw_normalize_date( gmdate( 'U' ) - ( $this->get_deletion_period() * DAY_IN_SECONDS ) );
	}
}

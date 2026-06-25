<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\DateTime;
use AutomateWoo\Logger;
use AutomateWoo\Trigger;
use AutomateWoo\Triggers\Utilities\CustomTimeOfDay;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractBatchedDailyTrigger
 *
 * @since 5.1.0
 */
abstract class AbstractBatchedDailyTrigger extends Trigger implements BatchedWorkflowInterface {

	use CustomTimeOfDay;

	/**
	 * Set that the trigger supports customer time of day functions
	 */
	const SUPPORTS_CUSTOM_TIME_OF_DAY = true;

	/**
	 * The site date this batch was scheduled to run for.
	 *
	 * @var DateTime|null
	 */
	private $batch_base_date = null;

	/**
	 * Set the site date this batch was scheduled to run for.
	 *
	 * @param string $date Site date in Y-m-d format.
	 */
	public function set_batch_base_date_from_site_date( string $date ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			Logger::warning( 'trigger', sprintf( 'Batched daily trigger "%s" received a malformed scheduled date "%s"; falling back to the current date.', $this->get_name(), $date ) );
			return;
		}

		[ $year, $month, $day ] = array_map( 'absint', explode( '-', $date ) );
		if ( ! checkdate( $month, $day, $year ) ) {
			Logger::warning( 'trigger', sprintf( 'Batched daily trigger "%s" received an invalid scheduled date "%s"; falling back to the current date.', $this->get_name(), $date ) );
			return;
		}

		$this->batch_base_date = new DateTime( $date . ' 12:00:00' );
		$this->batch_base_date->convert_to_utc_time();
	}

	/**
	 * Get the base date for this batch.
	 *
	 * @return DateTime
	 */
	protected function get_batch_base_date(): DateTime {
		return $this->batch_base_date ? clone $this->batch_base_date : new DateTime();
	}
}

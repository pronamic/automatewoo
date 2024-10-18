<?php

namespace AutomateWoo\Triggers;

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
}

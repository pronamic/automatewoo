<?php

namespace AutomateWoo\Entity;

/**
 * @class WorkflowTimingScheduled
 * @since 5.1.0
 */
class WorkflowTimingScheduled extends WorkflowTimingDelayed {

	const TYPE = 'scheduled';

	/**
	 * @var string
	 */
	protected $scheduled_time;

	/**
	 * @var int[] ISO-8601 numeric representation of the day of the week. 1 (for Monday) through 7 (for Sunday).
	 */
	protected $scheduled_days;

	/**
	 * @param int[]  $scheduled_days
	 * @param int    $scheduled_hour
	 * @param int    $scheduled_minute
	 * @param int    $delay_value
	 * @param string $delay_unit
	 */
	public function __construct( $scheduled_days, $scheduled_hour, $scheduled_minute, $delay_value, $delay_unit ) {
		parent::__construct( $delay_value, $delay_unit );

		$this->set_scheduled_time( $scheduled_hour, $scheduled_minute );
		$this->set_scheduled_days( $scheduled_days );
	}

	/**
	 * @return string
	 */
	public function get_scheduled_time() {
		return $this->scheduled_time;
	}

	/**
	 * @param int $hour
	 * @param int $minute
	 * @return $this
	 */
	public function set_scheduled_time( $hour = 0, $minute = 0 ) {
		$this->scheduled_time = sprintf( '%02d:%02d', min( (int) $hour, 23 ), min( (int) $minute, 59 ) );
		return $this;
	}

	/**
	 * @return int[]
	 */
	public function get_scheduled_days() {
		return $this->scheduled_days;
	}

	/**
	 * @param int[] $scheduled_days ISO-8601 numeric representation of the day of the week
	 * @return $this
	 */
	public function set_scheduled_days( $scheduled_days ) {
		$this->scheduled_days = $scheduled_days;
		return $this;
	}
}

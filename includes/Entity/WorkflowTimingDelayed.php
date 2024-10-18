<?php

namespace AutomateWoo\Entity;

/**
 * @class WorkflowTimingDelayed
 * @since 5.1.0
 */
class WorkflowTimingDelayed extends WorkflowTimingBase {

	const TYPE = 'delayed';

	const DELAY_UNIT_MINUTE = 'm';
	const DELAY_UNIT_HOUR   = 'h';
	const DELAY_UNIT_DAY    = 'd';
	const DELAY_UNIT_WEEK   = 'w';
	const DELAY_UNIT_MONTH  = 'month';

	/**
	 * @var int
	 */
	protected $delay_value;

	/**
	 * @var string
	 */
	protected $delay_unit;

	/**
	 * @param int    $delay_value
	 * @param string $delay_unit
	 */
	public function __construct( $delay_value, $delay_unit ) {
		$this->delay_value = $delay_value;
		$this->delay_unit  = $delay_unit;
	}

	/**
	 * @return int
	 */
	public function get_delay_value() {
		return $this->delay_value;
	}

	/**
	 * @param int $delay_value
	 * @return $this
	 */
	public function set_delay_value( $delay_value ) {
		$this->delay_value = $delay_value;
		return $this;
	}

	/**
	 * @return int
	 */
	public function get_delay_unit() {
		return $this->delay_unit;
	}

	/**
	 * @param int $delay_unit
	 * @return $this
	 */
	public function set_delay_unit( $delay_unit ) {
		$this->delay_unit = $delay_unit;
		return $this;
	}
}

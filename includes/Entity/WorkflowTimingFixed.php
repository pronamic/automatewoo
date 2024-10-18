<?php

namespace AutomateWoo\Entity;

use AutomateWoo\DateTime;

/**
 * @class WorkflowTimingFixed
 * @since 5.1.0
 */
class WorkflowTimingFixed extends WorkflowTimingBase {

	const TYPE = 'fixed';

	/**
	 * @var DateTime
	 */
	protected $fixed_date;

	/**
	 * @param DateTime $fixed_date
	 */
	public function __construct( DateTime $fixed_date ) {
		$this->fixed_date = $fixed_date;
	}

	/**
	 * @return DateTime
	 */
	public function get_fixed_datetime() {
		return $this->fixed_date;
	}

	/**
	 * @param DateTime $fixed_date
	 * @return $this
	 */
	public function set_fixed_datetime( DateTime $fixed_date ) {
		$this->fixed_date = $fixed_date;
		return $this;
	}
}

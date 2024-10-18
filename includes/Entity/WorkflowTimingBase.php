<?php

namespace AutomateWoo\Entity;

use LogicException;

/**
 * WorkflowTimingAbstract class.
 *
 * @since   5.1.0
 * @package AutomateWoo\Entity
 */
abstract class WorkflowTimingBase implements WorkflowTiming {

	const TYPE = '__TYPE__';

	/**
	 * Get the type of Workflow timing.
	 *
	 * @return string
	 * @throws LogicException When a child class doesn't override the TYPE constant.
	 */
	public function get_type() {
		if ( static::TYPE === self::TYPE ) {
			throw new LogicException( sprintf( '%s must override the TYPE constant.', static::class ) );
		}

		return static::TYPE;
	}
}

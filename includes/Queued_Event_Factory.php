<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Queued_Event_Factory
 * @since 2.9
 */
class Queued_Event_Factory extends Factory {

	/** @var string */
	public static $model = 'AutomateWoo\Queued_Event';

	/**
	 * @param int $id
	 * @return Queued_Event|bool
	 */
	public static function get( $id ) {
		return parent::get( $id );
	}
}

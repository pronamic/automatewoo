<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Queued_Event_Factory
 * @since 2.9
 */
class Queued_Event_Factory extends Factory {

	static $model = 'AutomateWoo\Queued_Event';

	/**
	 * @param int $id
	 * @return Queued_Event|bool
	 */
	static function get( $id ) {
		return parent::get( $id );
	}

}
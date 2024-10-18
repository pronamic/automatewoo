<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Log_Factory
 * @since 2.9
 */
class Log_Factory extends Factory {

	static $model = 'AutomateWoo\Log';

	/**
	 * @param int $id
	 * @return Log|bool
	 */
	static function get( $id ) {
		return parent::get( $id );
	}

}
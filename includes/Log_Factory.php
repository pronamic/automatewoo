<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Log_Factory
 * @since 2.9
 */
class Log_Factory extends Factory {

	/** @var string */
	public static $model = 'AutomateWoo\Log';

	/**
	 * @param int $id
	 * @return Log|bool
	 */
	public static function get( $id ) {
		return parent::get( $id );
	}
}

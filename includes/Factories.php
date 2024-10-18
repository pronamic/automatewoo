<?php

namespace AutomateWoo;

/**
 * @class Factories
 * @since 2.9
 */
class Factories {

	/** @var array */
	private static $factories;


	/**
	 * Get the Application factories
	 *
	 * @return array The factories
	 */
	public static function get_factories() {
		if ( ! isset( self::$factories ) ) {
			self::$factories = apply_filters(
				'automatewoo/factories',
				[
					'guest'    => 'AutomateWoo\Guest_Factory',
					'queue'    => 'AutomateWoo\Queued_Event_Factory',
					'log'      => 'AutomateWoo\Log_Factory',
					'cart'     => 'AutomateWoo\Cart_Factory',
					'customer' => 'AutomateWoo\Customer_Factory',
				]
			);
		}
		return self::$factories;
	}


	/**
	 * Gets a Factory
	 *
	 * @param string $type The factory to get
	 * @return bool|Factory
	 */
	public static function get_factory( $type ) {

		if ( ! $type ) {
			return false;
		}

		$factories = self::get_factories();

		return isset( $factories[ $type ] ) ? $factories[ $type ] : false;
	}


	/**
	 * Updates the cache for a Factory
	 *
	 * @param Model $object The object to update its factory cache
	 */
	public static function update_object_cache( $object ) {
		$factory = self::get_factory( $object->object_type );
		if ( $factory ) {
			$factory::update_cache( $object );
		} else {
			_doing_it_wrong( __FUNCTION__, esc_html( __( 'Factory class must be registered.', 'automatewoo' ) ), '2.9.0' );
		}
	}


	/**
	 * Cleans the cache for a Factory
	 *
	 * @param Model $object The object to clean its factory cache
	 */
	public static function clean_object_cache( $object ) {
		$factory = self::get_factory( $object->object_type );
		if ( $factory ) {
			$factory::clean_cache( $object );
		}
	}
}

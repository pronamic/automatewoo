<?php
namespace AutomateWoo\Admin;

use AutomateWoo\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin controller registry class
 */
class Controllers extends Registry {

	/** @var array */
	public static $includes;

	/** @var array */
	public static $loaded = [];

	/**
	 * Load the application Controller classes
	 *
	 * @return array The controller classes to be loaded
	 */
	public static function load_includes() {

		$path = AW()->admin_path( '/controllers/' );

		$includes = [
			'guests'    => $path . 'guests.php',
			'queue'     => $path . 'queue.php',
			'logs'      => $path . 'logs.php',
			'dashboard' => $path . 'dashboard.php',
			'carts'     => $path . 'carts.php',
			'reports'   => $path . 'reports.php',
			'settings'  => $path . 'settings.php',
			'tools'     => $path . 'tools.php',
			'opt-ins'   => $path . 'opt-ins.php',
			'preview'   => $path . 'preview.php',
		];

		return apply_filters( 'automatewoo/admin/controllers/includes', $includes );
	}


	/**
	 * Get all the controllers
	 *
	 * @return Controllers\Base[] All the controllers
	 */
	public static function get_all() {
		return parent::get_all();
	}


	/**
	 * Get one controller by name
	 *
	 * @param string $name The controller name
	 *
	 * @return Controllers\Base|false The controller
	 */
	public static function get( $name ) {
		return parent::get( $name );
	}


	/**
	 * Optional method to implement
	 *
	 * @param string           $name
	 * @param Controllers\Base $controller
	 */
	public static function after_loaded( $name, $controller ) {
		$controller->name = $name;
	}
}

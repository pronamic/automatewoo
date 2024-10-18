<?php

namespace AutomateWoo\Workflows\Presets;

/**
 * The base interface for presets
 *
 * @since 5.1.0
 */
interface PresetInterface {

	const NAME_KEY = 'name';

	const PRESET_TYPE_GUIDE = 'guide';

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( string $key ): bool;

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed|null
	 */
	public function get( string $key, $default = null );

	/**
	 * @param string     $key
	 * @param mixed|null $value
	 *
	 * @return mixed|null
	 */
	public function set( string $key, $value ): PresetInterface;

	/**
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * @param string $name
	 *
	 * @return PresetInterface
	 */
	public function set_name( string $name ): PresetInterface;
}

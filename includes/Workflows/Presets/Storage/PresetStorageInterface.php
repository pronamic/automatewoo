<?php

namespace AutomateWoo\Workflows\Presets\Storage;

use AutomateWoo\Workflows\Presets\PresetInterface;

/**
 * The storage interface used to fetch and read presets
 *
 * @since 5.1.0
 */
interface PresetStorageInterface {

	/**
	 * Returns the list of available presets
	 *
	 * @return PresetInterface[]
	 */
	public function list(): array;

	/**
	 * Returns a preset given its name
	 *
	 * @param string $name
	 *
	 * @return PresetInterface
	 */
	public function get( string $name ): PresetInterface;

	/**
	 * Checks if a preset exists
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function exists( string $name ): bool;
}

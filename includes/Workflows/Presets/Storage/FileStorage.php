<?php

namespace AutomateWoo\Workflows\Presets\Storage;

use AutomateWoo\Exceptions\InvalidPath;
use AutomateWoo\Logger;
use AutomateWoo\Workflows\Presets\PresetInterface;
use GlobIterator;
use SplFileInfo;

/**
 * @class FileStorage
 * @since 5.1.0
 */
abstract class FileStorage implements PresetStorageInterface {

	/**
	 * The file extension to search for.
	 *
	 * @var string
	 */
	protected $file_extension = '';

	/**
	 * The path to preset storage.
	 *
	 * @var string
	 */
	protected $storage_path = '';

	/**
	 * Any specific preset files to use.
	 *
	 * @var string[]
	 */
	protected $preset_files = [];

	/**
	 * FileStorage constructor.
	 *
	 * @param string $storage_path The path
	 * @param string $file_extension
	 *
	 * @throws InvalidPath When the storage path doesn't exist, or is not a directory.
	 */
	public function __construct( $storage_path, $file_extension ) {
		if ( ! file_exists( $storage_path ) ) {
			throw InvalidPath::file_does_not_exist( esc_html( $storage_path ) );
		}

		if ( ! is_dir( $storage_path ) ) {
			throw InvalidPath::path_not_directory( esc_html( $storage_path ) );
		}

		$this->storage_path   = $storage_path;
		$this->file_extension = $this->normalize_file_extension( $file_extension );
	}

	/**
	 * Returns the list of available presets
	 *
	 * @return PresetInterface[]
	 */
	public function list(): array {
		return array_map( [ $this, 'get_preset_data' ], array_keys( $this->find_presets() ) );
	}

	/**
	 * Returns a preset given its name
	 *
	 * @param string $name
	 *
	 * @return PresetInterface
	 *
	 * @throws StorageException When the preset does not exist.
	 */
	public function get( string $name ): PresetInterface {
		$this->validate_exists( $name );

		return $this->get_preset_data( $name );
	}

	/**
	 * Checks if a preset exists
	 *
	 * @param string $name The preset name.
	 *
	 * @return bool
	 */
	public function exists( string $name ): bool {
		return array_key_exists( $name, $this->find_presets() );
	}

	/**
	 * Validate that a preset exists.
	 *
	 * @param string $name
	 *
	 * @throws StorageException When the preset does not exist.
	 */
	protected function validate_exists( string $name ) {
		if ( ! $this->exists( $name ) ) {
			throw StorageException::preset_does_not_exist( esc_html( $name ) );
		}
	}

	/**
	 * Find available preset workflows.
	 *
	 * @return array
	 */
	protected function find_presets() {
		static $found_presets = null;
		if ( null !== $found_presets ) {
			return $found_presets;
		}

		$found_presets = [];

		// Load the manually specified preset files, or all files in the storage_path.
		if ( ! empty( $this->preset_files ) ) {
			$iterator = $this->get_defined_preset_files();
		} else {
			$iterator = new GlobIterator( "{$this->storage_path}/*{$this->file_extension}" );
		}

		/** @var SplFileInfo $file */
		foreach ( $iterator as $file ) {
			if ( $file->isDir() ) {
				continue;
			}

			$found_presets[ $file->getBasename( $this->file_extension ) ] = $file->getRealPath();
		}

		$found_presets = apply_filters( 'automatewoo/workflows/presets', $found_presets );

		if ( empty( $found_presets ) ) {
			Logger::notice( 'presets', sprintf( 'No valid presets found in %1s', $this->storage_path ) );
		}

		return $found_presets;
	}

	/**
	 * Return specified preset files that exist.
	 *
	 * @return SplFileInfo[] Array of valid preset files
	 */
	protected function get_defined_preset_files() {
		$iterator = [];
		foreach ( $this->preset_files as $filename ) {
			$file = new SplFileInfo( $this->storage_path . '/' . $filename );
			if ( ! $file->isFile() ) {
				Logger::notice(
					'presets',
					sprintf( 'Preset %1s expected but not found in %2s.', $filename, $this->storage_path )
				);
				continue;
			}
			$iterator[] = $file;
		}
		return $iterator;
	}

	/**
	 * Normalize a file extension to include the dot before the extension.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return string
	 */
	protected function normalize_file_extension( $extension ) {
		$extension = ltrim( strtolower( $extension ), '*.' );

		return ".{$extension}";
	}

	/**
	 * Get the data for a preset given its name.
	 *
	 * @param string $name The preset name.
	 *
	 * @return PresetInterface
	 */
	abstract protected function get_preset_data( string $name ): PresetInterface;
}

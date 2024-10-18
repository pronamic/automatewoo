<?php

namespace AutomateWoo\Workflows\Presets;

use AutomateWoo\Exceptions\Exception as ExceptionInterface;
use AutomateWoo\Logger;
use AutomateWoo\Workflow;
use AutomateWoo\Workflows\Factory;
use AutomateWoo\Workflows\Presets\Parser\ParserException;
use AutomateWoo\Workflows\Presets\Parser\PresetParserInterface;
use AutomateWoo\Workflows\Presets\Storage\PresetStorageInterface;
use AutomateWoo\Workflows\Presets\Storage\StorageException;
use WP_Error;

/**
 * @class PresetService
 * @since 5.1.0
 */
class PresetService {

	/**
	 * @var PresetStorageInterface
	 */
	protected $preset_storage;

	/**
	 * @var PresetParserInterface
	 */
	protected $preset_parser;

	/**
	 * PresetService constructor.
	 *
	 * @param PresetStorageInterface $preset_storage
	 * @param PresetParserInterface  $preset_parser
	 */
	public function __construct( PresetStorageInterface $preset_storage, PresetParserInterface $preset_parser ) {
		$this->preset_storage = $preset_storage;
		$this->preset_parser  = $preset_parser;
	}

	/**
	 * Returns the list of available presets
	 *
	 * @return PresetInterface[]
	 */
	public function get_presets() {
		return $this->preset_storage->list();
	}

	/**
	 * Returns a preset given its ID
	 *
	 * @param string $id
	 *
	 * @return PresetInterface|WP_Error Returns the preset if found or WP_Error if it doesn't exists
	 */
	public function get_preset( $id ) {
		try {
			return $this->preset_storage->get( $id );
		} catch ( StorageException $e ) {
			Logger::notice( 'presets', $e->getMessage() );

			/* translators: %s: The preset ID. */
			return new WP_Error( 'aw_preset_not_found', sprintf( __( 'The preset with ID "%s" does not exist.', 'automatewoo' ), $id ) );
		}
	}

	/**
	 * Stores the given preset as a draft workflow
	 *
	 * @param PresetInterface $preset
	 *
	 * @return Workflow|WP_Error Returns the created workflow on success, WP_Error on failure
	 */
	public function save_as_workflow( PresetInterface $preset ) {
		try {
			$workflow = $this->preset_parser->parse( $preset );

			$result = Factory::create( $workflow );
		} catch ( ParserException $e ) {
			Logger::notice( 'presets', $e->getMessage() );
			$result = new WP_Error( 'aw_invalid_preset', __( 'There were problems parsing the preset. Please check the logs for more info.', 'automatewoo' ) );
		} catch ( ExceptionInterface $e ) {
			Logger::notice( 'presets', $e->getMessage() );
			$result = new WP_Error( 'aw_preset_workflow', __( 'There were problems creating a workflow from the preset. Please check the logs for more info.', 'automatewoo' ) );
		} catch ( \Exception $e ) {
			Logger::notice( 'presets', $e->getMessage() );
			$result = new WP_Error( 'aw_preset_workflow', __( 'There were problems creating a workflow from the preset. Please check the logs for more info.', 'automatewoo' ) );
		}

		return $result;
	}

	/**
	 * Finds the preset given its ID and stores it as a draft workflow
	 *
	 * @param string $preset_id
	 *
	 * @return Workflow|WP_Error Returns the created workflow on success, WP_Error on failure
	 */
	public function save_as_workflow_by_id( $preset_id ) {
		try {
			$preset = $this->preset_storage->get( $preset_id );
		} catch ( ExceptionInterface $e ) {
			Logger::notice( 'presets', $e->getMessage() );
			return new WP_Error( 'aw_preset_get', __( 'There were problems retrieving the preset. Please check the logs for more info.', 'automatewoo' ) );
		}

		return $this->save_as_workflow( $preset );
	}
}

<?php

namespace AutomateWoo\Tools;

use AutomateWoo\Guest_Eraser;
use AutomateWoo\Options;
use AutomateWoo\OptionsStore;
use AutomateWoo\Tool_Abstract;
use AutomateWoo\Tool_Optin_Importer;
use AutomateWoo\Tool_Optout_Importer;
use AutomateWoo\Tool_Reset_Workflow_Records;

/**
 * Tools service class.
 *
 * @since 5.2.0
 */
class ToolsService {

	/**
	 * @var Tool_Abstract[] $tools
	 */
	protected $tools;

	/**
	 * @var OptionsStore
	 */
	protected $options_store;

	/**
	 * @param OptionsStore $options_store
	 */
	public function __construct( OptionsStore $options_store ) {
		$this->options_store = $options_store;
	}

	/**
	 * Get all tools.
	 *
	 * @return Tool_Abstract[]
	 */
	public function get_tools() {
		if ( isset( $this->tools ) ) {
			return $this->tools;
		}

		$class_names = [];

		$class_names[] = $this->options_store->get_optin_enabled() ? Tool_Optin_Importer::class : Tool_Optout_Importer::class;
		$class_names[] = Guest_Eraser::class;
		$class_names[] = Tool_Reset_Workflow_Records::class;

		$class_names = apply_filters( 'automatewoo/tools', $class_names );

		foreach ( $class_names as $tool_class ) {
			/** @var Tool_Abstract $class */
			$class                           = new $tool_class();
			$this->tools[ $class->get_id() ] = $class;
		}

		return $this->tools;
	}

	/**
	 * Get a single tool.
	 *
	 * @param string $id
	 *
	 * @return Tool_Abstract|false
	 */
	public function get_tool( string $id ) {
		$tools = $this->get_tools();

		if ( isset( $tools[ $id ] ) ) {
			return $tools[ $id ];
		}

		return false;
	}
}

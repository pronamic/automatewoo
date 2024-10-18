<?php

namespace AutomateWoo\Workflows\Presets\Parser;

use AutomateWoo\Entity\Workflow;
use AutomateWoo\Workflows\Presets\PresetInterface;

/**
 * The parser interface used to validate a preset and translate it to a workflow
 *
 * @since 5.1.0
 */
interface PresetParserInterface {

	/**
	 * Parses the preset data and returns a workflow entity based on it
	 *
	 * @param PresetInterface $preset
	 *
	 * @return Workflow
	 *
	 * @throws ParserException If there are any errors parsing the preset.
	 */
	public function parse( PresetInterface $preset );
}

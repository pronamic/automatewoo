<?php
/**
 * AutomateWoo abilities loader.
 *
 * @package AutomateWoo
 */

namespace AutomateWoo;

use AutomateWoo\Abilities\Get_Workflow_Health;
use AutomateWoo\Abilities\List_Workflows;
use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Wires AutomateWoo abilities into WooCommerce's ability loader.
 */
class Abilities {

	/**
	 * Initializes ability definition loading when WooCommerce supports it.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! interface_exists( AbilityDefinition::class ) ) {
			return;
		}

		add_filter( 'woocommerce_ability_definition_classes', [ __CLASS__, 'add_ability_definition_classes' ] );
	}

	/**
	 * Adds AutomateWoo ability definitions.
	 *
	 * @param array $classes Ability definition classes.
	 * @return array
	 */
	public static function add_ability_definition_classes( array $classes ): array {
		$classes[] = List_Workflows::class;
		$classes[] = Get_Workflow_Health::class;

		return $classes;
	}
}

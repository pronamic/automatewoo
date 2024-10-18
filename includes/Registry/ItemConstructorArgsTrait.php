<?php

namespace AutomateWoo\Registry;

/**
 * Trait ItemConstructorArgsTrait
 *
 * @since 5.3.0
 */
trait ItemConstructorArgsTrait {

	/**
	 * Get the constructor args for an item.
	 *
	 * @param string $name The item name.
	 *
	 * @return array
	 */
	protected static function get_item_constructor_args( string $name ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return [];
	}
}

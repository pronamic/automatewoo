<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Preloaded_Select_Rule_Abstract
 *
 * @since 4.6
 * @package AutomateWoo\Rules
 */
abstract class Preloaded_Select_Rule_Abstract extends Select_Rule_Abstract {

	/**
	 * Cached select options. Leave public for JSON.
	 *
	 * @var array
	 */
	public $select_choices;

	/**
	 * Load select choices for rule.
	 *
	 * @return array
	 */
	public function load_select_choices() {
		return [];
	}

	/**
	 * Get the select choices for the rule.
	 *
	 * Choices are cached in memory. Labels show as text, so tags are removed from them and
	 * any "<" left over is encoded.
	 *
	 * @since 6.9.6 Removes tags from the labels and encodes any "<" left over.
	 *
	 * @return array
	 */
	public function get_select_choices() {
		if ( ! isset( $this->select_choices ) ) {
			$choices = apply_filters( 'automatewoo/rules/preloaded_select/choices', $this->load_select_choices(), $this );

			$this->select_choices = is_array( $choices )
				? array_map(
					function ( $label ) {
						return is_string( $label ) && false !== strpos( $label, '<' ) ? wp_strip_all_tags( wp_pre_kses_less_than( $label ) ) : $label;
					},
					$choices
				)
				: $choices;
		}

		return $this->select_choices;
	}
}

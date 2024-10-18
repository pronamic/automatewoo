<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Searchable_Select_Rule_Abstract.
 *
 * Base class for rules that use an AJAX search to find values.
 *
 * @since 4.6
 * @package AutomateWoo\Rules
 */
abstract class Searchable_Select_Rule_Abstract extends Select_Rule_Abstract {

	/**
	 * The rule type.
	 *
	 * @var string
	 */
	public $type = 'object';

	/**
	 * The CSS class to use on the search field.
	 *
	 * @var string
	 */
	public $class = 'automatewoo-json-search';

	/**
	 * The field placeholder.
	 *
	 * @var string
	 */
	public $placeholder;

	/**
	 * Get the ajax action to use for the AJAX search.
	 *
	 * @return string
	 */
	abstract public function get_search_ajax_action();

	/**
	 * Init.
	 */
	public function init() {
		parent::init();

		$this->placeholder = __( 'Search...', 'automatewoo' );

		if ( ! $this->is_multi ) {
			$this->compare_types = $this->get_includes_or_not_compare_types();
		}
	}

	/**
	 * Override this method to alter how saved values are displayed.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function get_object_display_value( $value ) {
		return $value;
	}
}

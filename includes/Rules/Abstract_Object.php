<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

aw_deprecated_class( Abstract_Object::class, '5.2.0', Searchable_Select_Rule_Abstract::class );

/**
 * @class Abstract_Object
 * @deprecated in favour of AutomateWoo\Rules\Searchable_Select_Rule_Abstract
 */
abstract class Abstract_Object extends Rule {

	/** @var string  */
	public $type = 'object';

	/** @var bool  */
	public $is_multi = false;

	/** @var string */
	public $ajax_action;

	/** @var string  */
	public $class = 'automatewoo-json-search';

	/** @var string */
	public $placeholder;


	function __construct() {

		$this->placeholder = __( 'Search...', 'automatewoo' );

		parent::__construct();
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
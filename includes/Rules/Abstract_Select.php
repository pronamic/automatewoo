<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

aw_deprecated_class( Abstract_Select::class, '5.2.0', Preloaded_Select_Rule_Abstract::class );

/**
 * @deprecated in favour of Preloaded_Select_Rule_Abstract
 */
abstract class Abstract_Select extends Preloaded_Select_Rule_Abstract {

	/**
	 * Select_Rule_Abstract constructor.
	 */
	public function __construct() {
		parent::__construct();

		// for backwards compatibility
		if ( ! $this->compare_types ) {
			if ( $this->is_multi ) {
				$this->compare_types = $this->get_multi_select_compare_types();
			} else {
				$this->compare_types = $this->get_is_or_not_compare_types();
			}
		}
	}

}

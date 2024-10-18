<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Date
 */
class Date extends Text {

	/**
	 * Date constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->title = __( 'Date', 'automatewoo' );
		$this->name  = 'date';
		$this->set_placeholder( 'YYYY-MM-DD' );
		$this->add_extra_attr( 'pattern', '[0-9]{4}-[0-9]{2}-[0-9]{2}' );
		$this->add_extra_attr( 'autocomplete', 'off' );

		$this->add_classes( 'automatewoo-date-picker date-picker' );
	}
}

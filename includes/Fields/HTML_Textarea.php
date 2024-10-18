<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

defined( 'ABSPATH' ) || exit;

/**
 * Textarea field for raw HTML input.
 *
 * @class HTML_Textarea
 */
class HTML_Textarea extends Text_Area {

	/**
	 * Prevent decoding HTML entities before the field is rendered.
	 *
	 * @since 4.4.0
	 *
	 * @var bool
	 */
	public $decode_html_entities_before_render = false;


	/**
	 * HTML_Textarea constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_rows( 10 );
		$this->add_classes( 'automatewoo-field--monospace' );
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value The value of the field.
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		return Clean::email_content( $value );
	}
}

<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Text
 */
class Time extends Field {

	protected $name = 'time';

	protected $type = 'text';

	protected $show_24hr_note = true;

	/**
	 * Set the maximum value for the hours field.
	 *
	 * @var int
	 */
	public $max_hours = 23;

	function __construct() {
		parent::__construct();
		$this->title = __( 'Time', 'automatewoo' );
	}

	/**
	 * @param $show
	 * @return $this
	 */
	function set_show_24hr_note( $show ) {
		$this->show_24hr_note = $show;
		return $this;
	}


	/**
	 * @param array $value
	 */
	function render( $value ) {
		 if ( $value ) {
			 $value = Clean::recursive( (array) $value );
		 }
		 else {
			 $value = ['', ''];
		 }

		 ?>
		<div class="automatewoo-time-field-group">
			<div class="automatewoo-time-field-group__fields">
		<?php

		 $field = new Number();
		 $field
			 ->set_name_base( $this->get_name_base() )
			 ->set_name( $this->get_name() )
			 ->set_min( 0 )
			 ->set_max( $this->max_hours )
			 ->set_multiple()
			 ->set_placeholder( _x( 'HH', 'time field', 'automatewoo' ) )
			 ->set_required( $this->get_required() )
			 ->render( $value[0] );

		 echo '<div class="automatewoo-time-field-group__sep">:</div>';

		 $field = new Number();
		 $field
			 ->set_name_base( $this->get_name_base() )
			 ->set_name( $this->get_name() )
			 ->set_min( 0 )
			 ->set_max(59)
			 ->set_multiple()
			 ->set_placeholder( _x( 'MM', 'time field', 'automatewoo' ) )
			 ->set_required( $this->get_required() )
			 ->render( $value[1] );

		 ?>

			</div>

		<?php if ( $this->show_24hr_note ): ?>
			<span class="automatewoo-time-field-group__24hr-note"><?php esc_html_e( '(24 hour time)', 'automatewoo' ) ?></span>
		<?php endif; ?>

	    </div>

		<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	function sanitize_value( $value ) {
		$value = Clean::recursive( $value );

		$value[0] = min( $this->max_hours, $value[0] );

		return $value;
	}

}

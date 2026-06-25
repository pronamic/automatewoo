<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Formatters\Boolean_Formatter;
use AutomateWoo\Formatters\Formattable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Checkbox
 */
class Checkbox extends Field implements Formattable {

	use Boolean_Formatter;

	/** @var string */
	protected $name = 'checkbox';

	/** @var string */
	protected $type = 'checkbox';

	/** @var bool */
	public $default_to_checked = false;


	/**
	 * Checkbox constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Checkbox', 'automatewoo' ) );
	}


	/**
	 * @param bool $checked
	 * @return $this
	 */
	public function set_default_to_checked( $checked = true ) {
		$this->default_to_checked = $checked;
		return $this;
	}


	/**
	 * Output the field HTML.
	 *
	 * @param mixed $value
	 */
	public function render( $value ) {

		if ( $value === null || $value === '' ) {
			$value = $this->default_to_checked;
		}

		?>
		<input type="checkbox"
			name="<?php echo esc_attr( $this->get_full_name() ); ?>"
			value="1"
			<?php echo ( $value ? 'checked' : '' ); ?>
			class="<?php echo esc_attr( $this->get_classes() ); ?>"
			<?php $this->output_extra_attrs(); ?>
			>
		<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function sanitize_value( $value ) {
		return (bool) $value;
	}
}

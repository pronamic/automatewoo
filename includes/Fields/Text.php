<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Text
 */
class Text extends Field {

	protected $name = 'text_input';

	protected $type = 'text';

	public $multiple = false;

	/**
	 * Define whether HTML entities should be decoded before the field is rendered.
	 *
	 * @since 4.4.0
	 *
	 * @var bool
	 */
	public $decode_html_entities_before_render = true;


	function __construct() {
		parent::__construct();
		$this->title = __( 'Text Input', 'automatewoo' );
	}


	/**
	 * @param bool $multi
	 *
	 * @return $this
	 */
	function set_multiple( $multi = true ) {
		$this->multiple = $multi;
		return $this;
	}

	/**
	 * Output the field HTML.
	 *
	 * @param string $value
	 */
	function render( $value ) {
		if ( $this->decode_html_entities_before_render ) {
			$value = html_entity_decode( $value );
		}
	?>
		<input type="<?php echo esc_attr( $this->get_type() ) ?>"
		       name="<?php echo esc_attr( $this->get_full_name() ) ?><?php echo $this->multiple ? '[]' : '' ?>"
		       value="<?php echo esc_attr( $value ); ?>"
		       class="<?php echo esc_attr( $this->get_classes() ); ?>"
		       placeholder="<?php echo esc_attr( $this->get_placeholder() ) ?>"
			   <?php $this->output_extra_attrs(); ?>
			   <?php echo ( $this->get_required() ? 'required' : '' ) ?>
			>
	<?php
	}

}

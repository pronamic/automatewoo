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

	/**
	 * Whether to allow safe HTML tags when sanitizing the field value.
	 *
	 * @since 6.5.0
	 *
	 * @var bool
	 */
	protected $allow_html = false;


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
	 * Allow safe HTML tags when sanitizing the field value.
	 *
	 * @since 6.5.0
	 *
	 * @param bool $allow
	 *
	 * @return $this
	 */
	public function set_allow_html( $allow = true ) {
		$this->allow_html = $allow;
		return $this;
	}

	/**
	 * Sanitize the field value, preserving safe HTML when enabled.
	 *
	 * @since 6.5.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		if ( $this->allow_html ) {
			return wp_kses_post( $value );
		}
		return parent::sanitize_value( $value );
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

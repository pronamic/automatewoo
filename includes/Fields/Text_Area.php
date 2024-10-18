<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Text_Area
 */
class Text_Area extends Text {

	protected $name = 'text_area';

	protected $type = 'text_area';

	/**
	 * Determines if HTML will be allowed in the field value.
	 * By default HTML tags will be stripped.
	 *
	 * @var bool|string|array
	 */
	protected $allow_html = false;


	function __construct() {
		parent::__construct();
		$this->set_title( __( 'Text Area', 'automatewoo' ) );
	}

	/**
	 * @param int $rows
	 *
	 * @return $this
	 */
	function set_rows( $rows ) {
		$this->add_extra_attr('rows', $rows );
		return $this;
	}

	/**
	 * Determines if HTML will be allowed in the field value.
	 *
	 * @param bool|string|array $allow_html Default is false.
	 *  false - Strips all HTML
	 *  true - Sanitize content for allowed HTML tags in post content, uses wp_kses_post()
	 *  array|string - List of allowed HTML elements for wp_kses()
	 *
	 * @return $this
	 */
	function set_allow_html( $allow_html ) {
		$this->allow_html = $allow_html;
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
		<textarea
		       name="<?php echo esc_attr( $this->get_full_name() ); ?><?php echo $this->multiple ? '[]' : '' ?>"
		       class="<?php echo esc_attr( $this->get_classes() ); ?>"
		       placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
			   <?php $this->output_extra_attrs(); ?>
			   <?php echo ( $this->get_required() ? 'required' : '' ) ?>
			><?php echo esc_textarea( $value ); ?></textarea>
	<?php
	}

	/**
	 * Sanitizes the value of the field.
	 * Type of sanitization varies based on the value of $this->allow_html.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function sanitize_value( $value ) {
		if ( $this->allow_html ) {
			if ( $this->allow_html === true ) {
				return wp_kses_post( $value );
			} else {
				return wp_kses( $value, $this->allow_html );
			}
		} else {
			return Clean::textarea( $value );
		}
	}

}

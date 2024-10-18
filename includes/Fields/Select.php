<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Select
 */
class Select extends Field {

	protected $name = 'select';

	protected $type = 'select';

	protected $default_option;

	public $multiple = false;

	protected $options = [];

	public $dynamic_options_reference_field_name;


	/**
	 * @param bool $show_placeholder
	 */
	function __construct( $show_placeholder = true ) {
		parent::__construct();

		$this->set_title( __( 'Select', 'automatewoo' ) );

		if ( $show_placeholder ) {
			$this->set_placeholder( __( '[Select]', 'automatewoo' ) );
		}
	}


	/**
	 * @param $options
	 * @return $this
	 */
	function set_options( $options ) {
		$this->options = $options;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_options() {
		return $this->options;
	}


	/**
	 * @param $option
	 * @return $this
	 */
	function set_default( $option ) {
		$this->default_option = $option;
		return $this;
	}


	/**
	 * @param bool $multi
	 * @return $this
	 */
	function set_multiple( $multi = true ) {
		$this->multiple = $multi;
		return $this;
	}


	/**
	 * @param $reference_field_name
	 * @return $this
	 */
	function set_dynamic_options_reference( $reference_field_name ) {
		$this->dynamic_options_reference_field_name = $reference_field_name;
		return $this;
	}


	/**
	 * @return bool
	 */
	function has_dynamic_options() {
		return isset( $this->dynamic_options_reference_field_name );
	}


	/**
	 * @param $value
	 * @return void
	 */
	function render( $value = false ) {

		$value = $this->sanitize_value( $value );

		if ( $this->has_dynamic_options() ) {
			$this->add_data_attr( 'automatewoo-dynamic-select' );
			$this->add_data_attr( 'automatewoo-dynamic-select-reference', $this->dynamic_options_reference_field_name );
		}

		if ( $this->multiple ) {
			if ( ! $value ) {
				$value = $this->default_option ? $this->default_option : [];
			}

			$this->render_multiple( (array) $value );
		}
		else {
			if ( empty( $value ) && $this->default_option ) {
				$value = $this->default_option;
			}

			$this->render_single( (string) $value );
		}

	}


	/**
	 * Render a single select box.
	 *
	 * @param string $value
	 */
	protected function render_single( $value ) {
		?>

		<select name="<?php echo esc_attr( $this->get_full_name() ); ?>"
		        data-name="<?php echo esc_attr( $this->get_name() ); ?>"
		        class="<?php echo esc_attr( $this->get_classes() ); ?>"
		        <?php $this->output_extra_attrs() ?>
			    <?php echo ( $this->get_required() ? 'required' : '' ) ?>
		>

			<?php if ( $this->get_placeholder() ): ?>
				<option value=""><?php echo esc_html( $this->get_placeholder() ); ?></option>
			<?php endif; ?>

			<?php foreach( $this->get_options() as $opt_name => $opt_value ): ?>
				<?php if ( is_array($opt_value) ): ?>
					<optgroup label="<?php echo esc_attr( $opt_name ) ?>">
						<?php foreach( $opt_value as $opt_sub_name => $opt_sub_value ): ?>
							<option value="<?php echo esc_attr( $opt_sub_name ); ?>" <?php selected( $value, $opt_sub_name ); ?>><?php echo esc_html( $opt_sub_value ); ?></option>
						<?php endforeach?>
					</optgroup>
				<?php else: ?>
					<option value="<?php echo esc_attr( $opt_name ); ?>" <?php selected( $value, $opt_name ); ?>><?php echo esc_html( $opt_value ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>

		</select>

	<?php
	}


	/**
	 * Render a multi-select box.
	 *
	 * @param array $values
	 */
	protected function render_multiple( $values ) {
?>
		<select name="<?php echo esc_attr( $this->get_full_name() ); ?>[]"
		        data-name="<?php echo esc_attr( $this->get_name() ); ?>"
		        class="<?php echo esc_attr( $this->get_classes() ); ?> wc-enhanced-select"
		        multiple="multiple"
		        data-placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
			<?php $this->output_extra_attrs() ?>
		>

			<?php foreach( $this->get_options() as $opt_name => $opt_value ): ?>
				<option value="<?php echo esc_attr( $opt_name ); ?>"
					<?php echo in_array( $opt_name, $values ) ? 'selected="selected"' : ''; ?>
					><?php echo esc_html( $opt_value ); ?></option>
			<?php endforeach; ?>

		</select>

		<script type="text/javascript">
			jQuery( 'body' ).trigger( 'wc-enhanced-select-init' );
		</script>

<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	function sanitize_value( $value ) {
		if ( $this->multiple ) {
			return Clean::recursive( $value );
		}
		else{
			return Clean::string( $value );
		}
	}

}

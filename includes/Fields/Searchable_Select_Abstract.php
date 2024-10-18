<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

/**
 * Class Searchable_Select_Abstract
 *
 * Abstract base class for AJAX searchable select fields.
 *
 * @since 4.6.0
 * @package AutomateWoo\Fields
 */
abstract class Searchable_Select_Abstract extends Field {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	protected $type = 'searchable-select';

	/**
	 * Whether to allow multiple selections.
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	abstract protected function get_search_ajax_action();

	/**
	 * Searchable_Select_Abstract constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->classes[] = 'wc-product-search';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		return $value;
	}

	/**
	 * Output field HTML.
	 *
	 * @param int|array|string $values
	 */
	public function render( $values ) {
		$options = [];
		$values  = array_filter( (array) $values );

		foreach ( $values as $value ) {
			$options[ $value ] = $this->get_select_option_display_value( $value );
		}

		?>

		<select class="<?php echo esc_attr( $this->get_classes() ); ?>"
				<?php echo $this->is_multiple() ? 'multiple="multiple"' : ''; ?>
				name="<?php echo esc_attr( $this->get_full_name() ); ?><?php echo $this->is_multiple() ? '[]' : ''; ?>"
				data-name="<?php echo esc_attr( $this->get_name() ); ?>"
				data-allow_clear="true"
				data-placeholder="<?php esc_attr_e( 'Search&hellip;', 'automatewoo' ); ?>"
				data-action="<?php echo esc_attr( $this->get_search_ajax_action() ); ?>"
				<?php echo ( $this->get_required() ? 'required' : '' ); ?>>
			<?php
			foreach ( $options as $option_key => $option_value ) {
				echo '<option value="' . esc_attr( $option_key ) . '" selected="selected">' . wp_kses_post( $option_value ) . '</option>';
			}
			?>
		</select>

		<script type="text/javascript">
			jQuery( 'body' ).trigger( 'wc-enhanced-select-init' );
		</script>

		<?php
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_value( $value ) {
		if ( $this->is_multiple() ) {
			return Clean::recursive( $value );
		} else {
			return Clean::string( $value );
		}
	}

	/**
	 * Set multiple prop.
	 *
	 * @param bool $multiple
	 *
	 * @return $this
	 */
	public function set_multiple( $multiple ) {
		$this->multiple = $multiple;
		return $this;
	}

	/**
	 * Is multiple prop enabled.
	 *
	 * @return bool
	 */
	public function is_multiple() {
		return $this->multiple;
	}
}

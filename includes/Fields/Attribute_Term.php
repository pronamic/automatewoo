<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Attribute_Term
 */
class Attribute_Term extends Field {

	protected $name = 'term';

	protected $type = 'term';


	function __construct() {
		parent::__construct();
		$this->set_title( __( 'Terms', 'automatewoo' ) );
		$this->classes[] = 'automatewoo-json-search';
	}


	/**
	 * @param array $values
	 */
	function render( $values ) {

		$values = Clean::multi_select_values( $values );

		$display_values = [];

		foreach ( $values as $value ) {
			if ( strstr( $value, '|' ) ) {
				list( $term_id, $taxonomy ) = explode( '|', $value );

				if ( $term = get_term_by( 'id', $term_id, $taxonomy ) ) {
					$display_values[ $value ] = $term->name;
				}
			}
		}

		?>

		<select class="<?php echo esc_attr( $this->get_classes() ); ?>"
				  multiple="multiple"
				  name="<?php echo esc_attr( $this->get_full_name() ); ?>[]"
				  data-placeholder="<?php esc_attr_e( 'Search for a term&hellip;', 'automatewoo' ); ?>"
				  data-action="aw_json_search_attribute_terms"
				  data-pass-sibling="aw_workflow_data[trigger_options][attribute]"
		>
			<?php
			foreach ( $display_values as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $value ) . '</option>';
			}
			?>
		</select>

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
		return Clean::recursive( $value );
	}

}

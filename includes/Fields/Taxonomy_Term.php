<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Taxonomy_Term
 */
class Taxonomy_Term extends Field {

	protected $name = 'term';

	protected $type = 'term';


	function __construct() {
		parent::__construct();
		$this->classes[] = 'automatewoo-json-search';
		$this->set_title( __( 'Term', 'automatewoo' ) );
	}


	/**
	 * @param string $value
	 */
	function render( $value ) {
		$term  = false;
		$value = (string) $value;

		if ( strstr( $value, '|' ) ) {
			list( $term_id, $taxonomy ) = explode( '|', $value );
			$term = get_term_by( 'id', $term_id, $taxonomy );
		}

		?>

		<select type="hidden"
		        class="<?php echo esc_attr( $this->get_classes() ) ?>"
		        name="<?php echo esc_attr( $this->get_full_name() ); ?>"
		        data-placeholder="<?php esc_attr_e( 'Search for a term&hellip;', 'automatewoo' ); ?>"
		        data-action="aw_json_search_taxonomy_terms"
		        data-pass-sibling="aw_workflow_data[trigger_options][taxonomy]"
				<?php echo ( $this->get_required() ? 'required' : '' ) ?>
		>
			<?php
			if ( is_object( $term ) ) {
				echo '<option value="' . esc_attr( $term->term_id . '|' . $term->taxonomy ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $term->name ) . '</option>';
			}
			?>
		 </select>

	<?php
	}

}

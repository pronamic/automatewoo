<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Tag
 */
class Tag extends Field {

	protected $name = 'tag';

	protected $type = 'tag';


	function __construct() {
		$this->set_title( __( 'Product tag', 'automatewoo' ) );
		$this->set_placeholder( __( '[Select]', 'automatewoo' ) );
	}


	/**
	 * @param $value
	 */
	function render( $value ) {
		?>

		<select name="<?php echo esc_attr( $this->get_full_name() ); ?>"
		        class="wc-enhanced-select <?php echo esc_attr( $this->get_classes() ); ?>"
		        data-placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>">

			<option value=""><?php echo esc_html( $this->get_placeholder() ); ?></option>

			<?php

			$tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );

			if ( $tags ) foreach ( $tags as $tag ) {
				echo '<option value="' . esc_attr( $tag->term_id ) . '" ' . selected( $tag->term_id, $value, false ) . '>' . esc_html( $tag->name ) . '</option>';
			}
			?>
		</select>

		<script type="text/javascript">
			jQuery( 'body' ).trigger( 'wc-enhanced-select-init' );
		</script>

	<?php
	}
}

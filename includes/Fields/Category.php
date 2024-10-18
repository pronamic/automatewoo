<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Fields_Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Category
 */
class Category extends Field {

	protected $name = 'category';

	protected $type = 'category';


	function __construct() {
		parent::__construct();
		$this->set_title( __( 'Product category', 'automatewoo' ) );
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

            $categories = Fields_Helper::get_categories_list();

			foreach ( $categories as $category_id => $category_name ) {
				echo '<option value="' . esc_attr( $category_id ) . '" ' . selected( $category_id, $value, false ) . '>' . esc_html( $category_name ) . '</option>';
			}
			?>
		</select>

		<script type="text/javascript">
			jQuery( 'body' ).trigger( 'wc-enhanced-select-init' );
		</script>

	<?php
	}

}
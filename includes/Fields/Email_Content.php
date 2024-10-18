<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

defined( 'ABSPATH' ) || exit;

/**
 * Email_Content class.
 */
class Email_Content extends Field {

	/**
	 * Field name.
	 *
	 * @var string
	 */
	protected $name = 'email_content';

	/**
	 * Field type.
	 *
	 * @var string
	 */
	protected $type = 'email-content';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Email content', 'automatewoo' ) );
		$this->set_description( __( 'The contents of this field will be formatted as per the selected email template.', 'automatewoo' ) );
	}

	/**
	 * Render the field.
	 *
	 * @param string $value
	 */
	public function render( $value ) {

		$id    = uniqid();
		$value = Clean::email_content( $value );

		wp_editor(
			$value,
			$id,
			[
				'textarea_name'  => $this->get_full_name(),
				'tinymce'        => true, // load TinyMCE
				'default_editor' => 'tinymce', // default to visual
				'quicktags'      => true,
			]
		);

		if ( wp_doing_ajax() ) {
			$this->ajax_init( $id );
		}
	}

	/**
	 * Insert script to init the WYSIWYG editor.
	 *
	 * @param string $id
	 */
	public function ajax_init( $id ) {
		?>
		<script type="text/javascript">
			(function(){
				AutomateWoo.Workflows.init_ajax_wysiwyg('<?php echo esc_js( $id ); ?>');
			}());
		</script>
		<?php
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		return Clean::email_content( $value );
	}
}

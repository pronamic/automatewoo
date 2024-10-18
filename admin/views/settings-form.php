<?php
/**
 * @package  AutomateWoo/Admin/Views
 *
 * @var AutomateWoo\Admin_Settings_Tab_Abstract $tab
 */

defined( 'ABSPATH' ) || exit;

?>


<form method="post" id="mainform" action="" enctype="multipart/form-data">

	<?php $tab->output_settings_fields(); ?>

	<div class="automatewoo-settings-submit">
		<input type="hidden" name="action" value="automatewoo-settings">
		<input name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'automatewoo' ); ?>"><br><br><br>
		<?php wp_nonce_field( 'automatewoo-settings' ); ?>
	</div>

</form>

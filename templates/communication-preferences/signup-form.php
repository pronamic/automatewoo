<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/communication-preferences/signup-form.php
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<?php wc_print_notices() ?>

<div class="aw-communication-page woocommerce">

	<form action="" class="aw-communication-form" method="post">

		<p class="aw-communication-form__email-field woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="automatewoo_communication_email"><?php _e( 'Email address', 'automatewoo' ); ?></label>
			<input type="email" name="email"
			       class="woocommerce-Input woocommerce-Input--text input-text"
			       id="automatewoo_communication_email"
			       autocomplete="email"
			       value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : '' ?>">
		</p>

		<?php aw_get_template('communication-preferences/communication-preferences-list.php' ); ?>
	    <?php aw_get_template('communication-preferences/communication-terms-text.php' ); ?>

		<p>
			<?php wp_nonce_field( 'automatewoo_save_communication_signup' ); ?>
			<input type="hidden" name="action" value="automatewoo_save_communication_signup">
			<input type="submit" class="woocommerce-Button button aw-communication-form__submit" name="automatewoo_save_changes" value="<?php _e( 'Save changes', 'automatewoo' ) ?>">
			<?php aw_get_template( 'honeypot-field.php' ); ?>
		</p>


	</form>

</div>


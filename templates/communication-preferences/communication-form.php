<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/communication-preferences/preferences-form.php
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Customer data is required for this form.
 * @var Customer $customer
 */


?>

<?php wc_print_notices() ?>

<div class="aw-communication-page woocommerce">

	<form action="" class="aw-communication-form" method="post">

		<div class="aw-communication-form__intro-text">
			<p><?php printf( __( 'You are managing preferences for %s.', 'automatewoo' ), make_clickable( $customer->get_email() ) ) ?></p>
		</div>

		<?php aw_get_template('communication-preferences/communication-preferences-list.php', [ 'customer' => $customer ] ); ?>
		<?php aw_get_template('communication-preferences/communication-terms-text.php' ); ?>

		<p>
			<?php wp_nonce_field( 'automatewoo_save_communication_preferences' ); ?>
			<input type="hidden" name="customer_key" value="<?php echo esc_attr( $customer->get_key() ); ?>">
			<input type="hidden" name="action" value="automatewoo_save_communication_preferences">
			<input type="submit" class="woocommerce-Button button aw-communication-form__submit" name="automatewoo_save_changes" value="<?php _e( 'Save changes', 'automatewoo' ) ?>">
		</p>

	</form>

</div>


<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/honeypot-field.php
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p class="aw-communication-form__text-field woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide aw-hidden" aria-hidden="true">
	<label for="automatewoo_hp_firstname" aria-hidden="true"><?php _e( 'First name', 'automatewoo' ); ?>:</label>
	<input type="text" name="<?php echo esc_attr( apply_filters( 'automatewoo/honeypot_field/name', 'firstname' ) ); ?>"
	       id="automatewoo_hp_firstname"
	       class="woocommerce-Input woocommerce-Input--email input-text"
	       autocomplete="nope"
	       aria-hidden="true">
</p>
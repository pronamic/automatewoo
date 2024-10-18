<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/optin-checkbox.php
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<p class="automatewoo-optin form-row">
	<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
		<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="automatewoo_optin" <?php checked( isset( $_POST['automatewoo_optin'] ), true ); ?> id="automatewoo_optin" />
		<span class="automatewoo-optin__checkbox-text"><?php echo Options::optin_checkbox_text() ?></span>
	</label>
</p>
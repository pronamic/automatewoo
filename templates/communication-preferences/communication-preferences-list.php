<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/communication-preferences/communication-preferences-list.php
 */

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @var Customer|false $customer
 */

if ( ! isset( $customer ) ) {
	$customer = false;
}

if ( Frontend_Form_Handler::$current_action === 'save_communication_signup' ) {
	// form was just submitted
	$default = isset( $_POST['subscribe'] );
}
else {
	// default is used on signup page only, when there is no customer
	$default = apply_filters( 'automatewoo/communication_preferences/default_to_checked', true );
}

?>

<div class="aw-communication-form__preference-list">

	<div class="aw-communication-form__preference">
		<div class="aw-communication-form__preference-inner">

			<input type="checkbox"
					 name="subscribe"
					 id="automatewoo_communication_page_subscribe_checkbox"
					 class="aw-communication-form__preference-checkbox"
				<?php checked( $customer ? ! $customer->is_unsubscribed() : $default ) ?>>

			<div class="aw-communication-form__preference-text">
				<label class="aw-communication-form__preference-title" for="automatewoo_communication_page_subscribe_checkbox"><?php _e('Updates about products and promotions', 'automatewoo' ); ?></label>
				<p class="aw-communication-form__preference-description"><?php _e( 'Receive marketing communications that we think you will be interested in.', 'automatewoo' ); ?></p>
			</div>
		</div>
	</div>

	<?php do_action( 'automatewoo/communication_form/after_subscribe_preference', $customer ); ?>

	<div class="aw-communication-form__preference aw-communication-form__preference--disabled">
		<div class="aw-communication-form__preference-inner">

			<input type="checkbox" class="aw-communication-form__preference-checkbox" checked="checked" disabled="disabled">

			<div class="aw-communication-form__preference-text">
				<label class="aw-communication-form__preference-title"><?php _e( 'Account and order information', 'automatewoo' ) ?></label>
				<p class="aw-communication-form__preference-description"><?php _e( 'Receive important information about your orders and account.', 'automatewoo' ) ?></p>
			</div>
		</div>
	</div>

</div>

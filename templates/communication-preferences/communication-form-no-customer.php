<?php
/**
 * Override this template by copying it to yourtheme/automatewoo/communication-preferences/preferences-form-no-customer.php
 */

namespace AutomateWoo;

/**
 * @var string|bool $intent
 */

defined( 'ABSPATH' ) || exit;

if ( $intent === 'unsubscribe' ) {
	wc_add_notice( __( "We couldn't find any customer data matching your request. Your account may have been deleted.", 'automatewoo' ), 'notice' );
} elseif ( $intent === 'link-expired' ) {
	wc_add_notice( __( 'This link has expired. Sign in to your account to manage your communication preferences.', 'automatewoo' ), 'notice' );
	wc_add_notice( __( "If you don't have an account, use the unsubscribe link in your next email from us.", 'automatewoo' ), 'notice' );
} elseif ( $intent === 'link-not-for-unsubscribe' ) {
	wc_add_notice( __( "This link can't be used to unsubscribe, so we didn't change any preferences.", 'automatewoo' ), 'notice' );
	wc_add_notice( __( 'To unsubscribe, use the unsubscribe link in a recent email from us, or sign in to your account to manage your communication preferences.', 'automatewoo' ), 'notice' );
} else {
	$text = sprintf(
		/* translators: %1$s my account link start, %2$s my account link end. */
		__( '%1$sSign in to your account%2$s to manage your communication preferences.', 'automatewoo' ),
		'<a href="' . wc_get_page_permalink( 'myaccount' ) . '">',
		'</a>'
	);
	wc_add_notice( $text, 'notice' );
}

wc_print_notices();

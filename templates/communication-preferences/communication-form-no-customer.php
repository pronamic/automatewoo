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

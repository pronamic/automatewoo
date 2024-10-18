<?php
// phpcs:ignoreFile
/**
 * Override this template by copying it to yourtheme/automatewoo/communication-preferences/preferences-form-no-customer.php
 */

namespace AutomateWoo;

/**
 * @var string|bool $intent
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( $intent === 'unsubscribe' ) {
	wc_add_notice( __( "We couldn't find any customer data matching your request. Your account may have been deleted.", 'automatewoo' ), 'notice' );
}
else {
	$text = sprintf( __( "<%s>Sign in to your account<%s> to manage your communication preferences.", 'automatewoo' ),
	   'a href="' . wc_get_page_permalink( 'myaccount' ) . '"',
	   '/a'
	);
	wc_add_notice($text, 'notice' );
}


?>

<?php wc_print_notices() ?>




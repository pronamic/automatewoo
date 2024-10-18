<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var $iframe_url string
 * @var $type string
 * @var $email_subject string
 * @var $template string
 * @var $args array
 */

$test_emails = get_user_meta( get_current_user_id(), 'automatewoo_email_preview_test_emails', true );
if ( ! $test_emails ) {
	$user        = wp_get_current_user();
	$test_emails = $user->user_email;
}

$from_name    = Emails::get_from_name( $template );
$from_address = Emails::get_from_address( $template );
?>

<div class="aw-preview">
	<div class="aw-preview__header">

		<div class="aw-preview__header-left">
			<div class="from">
				<strong><?php esc_html_e( 'From', 'automatewoo' ); ?>:</strong> <?php echo esc_html( $from_name ); ?> &lt;<?php echo esc_html( $from_address ); ?>&gt;
			</div>
			<div class="from"><strong><?php esc_html_e( 'Subject', 'automatewoo' ); ?>:</strong> <?php echo esc_html( $email_subject ); ?></div>
		</div>

		<div class="aw-preview__header-right">
			<form class="aw-preview__send-test-form">
				<input type="text"
					value="<?php echo esc_attr( $test_emails ); ?>"
					name="to_emails"
					class="email-input"
					placeholder="<?php esc_attr_e( 'Comma separate emails...', 'automatewoo' ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
				<input type="hidden" name="args" value='<?php echo aw_esc_json( wp_json_encode( $args ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'>

				<button type="submit" class="button-secondary"><?php esc_html_e( 'Send', 'automatewoo' ); ?></button>
			</form>
		</div>
	</div>

	<iframe class="aw-preview__email-iframe" src="<?php echo esc_url( $iframe_url ); ?>" width="100%" frameborder="0"></iframe>
</div>

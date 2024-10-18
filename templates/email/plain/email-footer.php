<?php
// phpcs:ignoreFile
/**
 * Plain email footer
 *
 * Override this template by copying it to yourtheme/automatewoo/email/plain/email-footer.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<?php if ( apply_filters( 'automatewoo/show_plain_text_footer_when_no_unsubscribe_link', false ) || AW_Mailer_API::unsubscribe_url() ): // only show footer when there is unsub URL ?>
	<div class="automatewoo-plain-email-footer">
		<br><span>&ndash;</span><br>
		<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ?>
	</div>
<?php endif; ?>

</body></html>

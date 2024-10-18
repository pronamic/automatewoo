<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Privacy_Policy_Guide
 * @since 4.0
 */
class Privacy_Policy_Guide {


	/**
	 * @return string
	 */
	static function get_content() {
		ob_start();
		?>
<div class="wp-suggested-text">
	<h2><?php _e( 'How we use your data', 'automatewoo' ); ?></h2>
	<p class="privacy-policy-tutorial"><?php _e( 'AutomateWoo uses personal data in many of its features and stores personal data in your WordPress database. We do not store any personal data from your website on our servers. You may need to adjust the suggested text below based on your settings and which workflows are in use.', 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php _e( 'If you opt-in to receive marketing updates we may use your personal information to provide you with product updates or marketing communications that we believe may be of interest to you. Personal data may also be used by our internal system to automate processes of our store.', 'automatewoo' ); ?></p>
	<h2><?php _e( 'What we collect and store', 'automatewoo' ); ?></h2>
	<h3><?php _e( 'Cookies', 'automatewoo' ); ?></h3>
	<p class="privacy-policy-tutorial"><?php _e( 'AutomateWoo uses three cookies to enable the session and cart tracking features of the plugin. If you have these features enabled you should include these cookies in your policy. You should also mention whether the user must give consent before these cookies are set.', 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php _e( 'We use cookies to remember who you are when browsing our site and to store the contents of your cart for the purpose of reminding you. These cookies will only be set when you consent to allowing additional cookies on our website.', 'automatewoo' ); ?></p>
	<p>
		<?php printf( __( '%s - Used to store a secure key that is unique to you - Expires after 2 years', 'automatewoo' ), '<strong>wp_automatewoo_visitor</strong>' ); ?><br>
		<?php printf( __( '%s - Used to flag when you begin interacting with our website - Expires when you end the browser session', 'automatewoo' ), '<strong>wp_automatewoo_session_started</strong>' ); ?><br>
		<?php printf( __( '%s - Used to store flag when your stored cart needs to be updated - Expires when you end the browser session', 'automatewoo' ), '<strong>automatewoo_do_cart_update</strong>' ); ?>
	</p>
	<h3><?php _e( 'Carts', 'automatewoo' ); ?></h3>
	<p class="privacy-policy-tutorial"><?php _e( "If you are using AutomateWoo's cart tracking feature user carts will be stored for 60 days, depending on your settings.", 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php _e( 'We store a copy of your cart in our database for 60 days for the purpose of reminding you when your cart is abandoned.', 'automatewoo' ); ?></p>
	<h3><?php _e( 'Communication preferences', 'automatewoo' ); ?></h3>
	<p class="privacy-policy-tutorial"><?php _e( "AutomateWoo keeps a record of when a user or guest chooses to opt-in or opt-out.", 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php _e( 'We store your communication preferences such as whether you have opted in to receive marketing communication. This data is retained until you request the removal of your data.', 'automatewoo' ); ?></p>
	<h3><?php _e( 'Communication logs', 'automatewoo' ); ?></h3>
	<p class="privacy-policy-tutorial"><?php _e( "AutomateWoo keeps a record of all workflow logs which includes open, click and conversion tracking.", 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php _e( 'We keep a log of some of the communication that we have with you which may include marketing and transactional emails and/or SMS messages. These are kept for the purpose of improving our marketing and communication with you and other customers. These logs are retained until you request removal of your data.', 'automatewoo' ); ?></p>
	<h3><?php _e( 'Pre-submit data capture', 'automatewoo' ); ?></h3>
	<p class="privacy-policy-tutorial"><?php _e( "AutomateWoo has the option to capture customer data before forms are submitted which usually means that consent has not been given. Using this feature may not be legal in some regions depending on your legal basis for capturing that data. If you choose to use this feature we recommend including some reasoning behind it.", 'automatewoo' ); ?></p>
	<h2><?php _e( 'What we share with others', 'automatewoo' ); ?></h2>
	<p class="privacy-policy-tutorial"><?php _e( "AutomateWoo integrates with third party services which means personal data may be shared depending on what integrations you have enabled and which workflows you are using.", 'automatewoo' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use Twilio as our SMS delivery service. Your data may be transferred to Twilio for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://www.twilio.com/legal/privacy" target="_blank"', '/a' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use MailChimp for email marketing. Your data may be transferred to MailChimp for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://mailchimp.com/legal/privacy/" target="_blank"', '/a' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use Active Campaign for email marketing. Your data may be transferred to Active Campaign for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://www.activecampaign.com/privacy-policy/" target="_blank"', '/a' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use Campaign Monitor for email marketing. Your data may be transferred to Campaign Monitor for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://www.campaignmonitor.com/policies/#privacy-policy" target="_blank"', '/a' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use Bitly for link shortening. Your data may be transferred to Bitly for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://bitly.com/pages/privacy" target="_blank"', '/a' ); ?></p>
	<p><?php self::suggest_text_html() ?> <?php printf(__( "We use AgileCRM as our CRM. Your data may be transferred to AgileCRM for processing in accordance with their <%s>Privacy Policy<%s>.", 'automatewoo' ), 'a href="https://www.agilecrm.com/privacy-policy" target="_blank"', '/a' ); ?></p>
</div>
		<?php
		$content = ob_get_clean();
		return apply_filters( 'automatewoo/privacy_policy_guide', $content );
	}


	protected static function suggest_text_html() {
		?><strong class="privacy-policy-tutorial"><?php _e( 'Suggested text:', 'automatewoo' ); ?></strong><?php
	}


}

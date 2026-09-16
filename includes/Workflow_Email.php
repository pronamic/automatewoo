<?php

namespace AutomateWoo;

/**
 * @class Workflow_Email
 * @since 2.8.6
 */
class Workflow_Email {

	/**
	 * The type of the email.
	 * Default: 'html-template'
	 *
	 * @since 4.4.0
	 * @var string (html-template, html-raw, plain-text)
	 */
	protected $type = 'html-template';

	/** @var Workflow  */
	public $workflow;

	/** @var string */
	public $recipient;

	/** @var string */
	public $subject;

	/**
	 * The content of the email.
	 *
	 * @var string
	 */
	public $content;

	/** @var string */
	public $heading;

	/** @var string */
	private $preheader;

	/**
	 * Reply to email address.
	 *
	 * @var string
	 */
	private $reply_to = '';

	/** @var array */
	private $cc = [];

	/** @var array */
	private $bcc = [];

	/** @var string */
	public $template;

	/** @var bool */
	protected $tracking_enabled = false;

	/**
	 * Memoized recipient customer, see get_recipient_customer().
	 *
	 * @var \AutomateWoo\Customer|false|null
	 */
	private $recipient_customer;

	/**
	 * The address $recipient_customer was looked up for, so a new recipient invalidates it.
	 *
	 * @var string|null
	 */
	private $recipient_customer_email;

	/** @var bool */
	public $include_automatewoo_styles = true;


	/**
	 * @param Workflow $workflow  The workflow that is sending the email.
	 * @param string   $recipient The email address of the recipient. Must be a single email.
	 * @param string   $subject   The email subject.
	 * @param string   $content   The main email content. Depending on the $type property this can be raw HTML (html-raw),
	 *                            plain text (plain-text) or content to be wrapped in a template (html-template).
	 */
	public function __construct( Workflow $workflow, string $recipient, string $subject, string $content ) {
		$this->workflow = $workflow;
		$this->set_recipient( $recipient );
		$this->set_subject( $subject );
		$this->set_content( $content );

		if ( $workflow->is_tracking_enabled() ) {
			$this->set_tracking_enabled( true );
		}
	}

	/**
	 * Set the email type.
	 *
	 * @since 4.4.0
	 *
	 * @param string $type (html-template, html-raw, plain-text)
	 *
	 * @return $this
	 */
	public function set_type( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * Get the email type.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Check the email type.
	 *
	 * @since 4.4.0
	 *
	 * @param string $type (html-template, html-raw, plain-text)
	 *
	 * @return bool
	 */
	public function is_type( $type ) {
		return $this->get_type() === $type;
	}


	/**
	 * @param string $recipient
	 *
	 * @return $this
	 */
	public function set_recipient( $recipient ) {
		$this->recipient = $recipient;

		return $this;
	}


	/**
	 * @param string $subject
	 *
	 * @return $this
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;

		return $this;
	}


	/**
	 * Set the content of the email.
	 *
	 * This can be raw HTML, plain text or content to be wrapped in a template, depending on the $type.
	 *
	 * @param string $content
	 *
	 * @return $this
	 */
	public function set_content( $content ) {
		$this->content = $content;

		return $this;
	}


	/**
	 * @param string $heading
	 *
	 * @return $this
	 */
	public function set_heading( $heading ) {
		$this->heading = $heading;

		return $this;
	}


	/**
	 * @param string $preheader
	 *
	 * @return $this
	 */
	public function set_preheader( $preheader ) {
		$this->preheader = $preheader;

		return $this;
	}


	/**
	 * @param string $template
	 *
	 * @return $this
	 */
	public function set_template( $template ) {
		$this->template = $template;

		return $this;
	}


	/**
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function set_tracking_enabled( $enabled ) {
		$this->tracking_enabled = $enabled;

		return $this;
	}

	/**
	 * Is tracking on for this send, taking the recipient's own choice into account?
	 *
	 * The workflow flag and the --notracking token both feed $this->tracking_enabled.
	 * On top of that, a recipient who opted out is never tracked: no pixel and no
	 * rewritten links, the same shape as --notracking, for that send only.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	protected function is_tracking_enabled_for_recipient() {
		if ( ! $this->tracking_enabled ) {
			return false;
		}

		$customer = $this->get_recipient_customer();

		return ! ( $customer && $customer->is_tracking_opted_out() );
	}

	/**
	 * The AutomateWoo customer behind the recipient address, or false if there isn't one.
	 *
	 * Memoized because sending one email asks for it up to five times: the tracking
	 * check before rendering, then again for each footer link and each opt-out URL,
	 * HTML and plain text. Every miss was an uncached database read. Keyed on the
	 * address so set_recipient() invalidates it.
	 *
	 * @since x.x.x
	 *
	 * @return \AutomateWoo\Customer|false
	 */
	protected function get_recipient_customer() {
		if ( $this->recipient_customer_email !== $this->recipient ) {
			// Never create a customer record just to read consent or build a link, hence the false.
			$this->recipient_customer       = Customer_Factory::get_by_email( $this->recipient, false );
			$this->recipient_customer_email = $this->recipient;
		}

		return $this->recipient_customer;
	}


	/**
	 * @param bool $include
	 *
	 * @return $this
	 */
	public function set_include_automatewoo_styles( $include ) {
		$this->include_automatewoo_styles = $include;

		return $this;
	}

	/**
	 * Set the reply to address for the email.
	 *
	 * @since 4.9.0
	 *
	 * @param string $reply_to e.g. 'John Smith <email@example.org>'.
	 *
	 * @return $this
	 */
	public function set_reply_to( $reply_to ) {
		$this->reply_to = $reply_to;

		return $this;
	}

	/**
	 * Set CC recipients for the email.
	 *
	 * @since 6.2.1
	 *
	 * @param array $cc Array of email addresses.
	 *
	 * @return $this
	 */
	public function set_cc( array $cc ) {
		$this->cc = $cc;
		return $this;
	}

	/**
	 * Set BCC recipients for the email.
	 *
	 * @since 6.2.1
	 *
	 * @param array $bcc Array of email addresses.
	 *
	 * @return $this
	 */
	public function set_bcc( array $bcc ) {
		$this->bcc = $bcc;
		return $this;
	}

	/**
	 * Get CC recipients for the email.
	 *
	 * @since 6.2.1
	 *
	 * @return array
	 */
	public function get_cc() {
		return $this->cc;
	}

	/**
	 * Get BCC recipients for the email.
	 *
	 * @since 6.2.1
	 *
	 * @return array
	 */
	public function get_bcc() {
		return $this->bcc;
	}

	/**
	 * @return Mailer|Mailer_Raw_HTML|Mailer_Plain_Text
	 */
	public function get_mailer() {
		$tracking_enabled = $this->is_tracking_enabled_for_recipient();

		if ( $this->is_type( 'plain-text' ) ) {
			$content = $this->get_content_with_appended_plain_text_footer();

			if ( $tracking_enabled ) {
				$content = $this->replace_plain_text_urls( $content );
			}

			$mailer = new Mailer_Plain_Text();
			$mailer->set_content( $content );
		} else {
			if ( $this->is_type( 'html-raw' ) ) {
				$mailer = new Mailer_Raw_HTML();
			} else {
				$mailer = new Mailer();
				$mailer->set_template( $this->template );
				$mailer->set_heading( $this->heading );
				$mailer->set_preheader( $this->preheader );
				$mailer->extra_footer_text = $this->get_footer_links();
			}

			$allowed_html          = wp_kses_allowed_html( 'post' );
			$allowed_html['style'] = array();

			$mailer->set_content( wp_kses( $this->content, $allowed_html ) );
			$mailer->set_include_automatewoo_styles( $this->include_automatewoo_styles );

			if ( $tracking_enabled ) {
				$mailer->tracking_pixel_url            = Tracking::get_open_tracking_url( $this->workflow );
				$mailer->replace_content_urls_callback = [ $this, 'replace_content_urls_callback' ];
			}
		}

		$mailer->set_email( $this->recipient );
		$mailer->set_subject( $this->subject );

		if ( $this->reply_to ) {
			$mailer->set_reply_to( $this->reply_to );
		}

		if ( ! empty( $this->cc ) ) {
			$mailer->set_cc( $this->cc );
		}

		if ( ! empty( $this->bcc ) ) {
			$mailer->set_bcc( $this->bcc );
		}

		return apply_filters( 'automatewoo/workflow/mailer', $mailer, $this );
	}


	/**
	 * Get the unsubscribe link HTML.
	 *
	 * @return bool|string
	 */
	public function get_unsubscribe_link() {
		$url  = $this->get_unsubscribe_url();
		$text = $this->get_unsubscribe_text();

		if ( ! $url || ! $text ) {
			return false;
		}

		return '<a href="' . $url . '" class="automatewoo-unsubscribe-link" target="_blank">' . $text . '</a>';
	}

	/**
	 * Get the unsubscribe link for the recipient.
	 *
	 * @since 4.4.0
	 *
	 * @return bool|string
	 */
	public function get_unsubscribe_url() {
		$customer = Customer_Factory::get_by_email( $this->recipient );
		return $this->workflow->get_unsubscribe_url( $customer );
	}

	/**
	 * Get the unsubscribe text.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_unsubscribe_text() {
		return apply_filters( 'automatewoo_email_unsubscribe_text', __( 'Unsubscribe', 'automatewoo' ), $this, $this->workflow );
	}

	/**
	 * URL the recipient can use to turn off open and click tracking.
	 *
	 * False when there is nothing to opt out of, or nobody to record the choice against.
	 * Unlike the unsubscribe link this is NOT suppressed for transactional workflows: a
	 * transactional email can still be tracked, so the consent is owed either way.
	 *
	 * @since x.x.x
	 *
	 * @return bool|string
	 */
	public function get_tracking_opt_out_url() {
		if ( ! $this->is_tracking_enabled_for_recipient() ) {
			return false;
		}

		$customer = $this->get_recipient_customer();

		if ( ! $customer ) {
			return false;
		}

		return Frontend::get_communication_page_permalink( $customer, Communication_Page::INTENT_TRACKING_OPT_OUT );
	}

	/**
	 * Link text for the tracking opt-out.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_tracking_opt_out_text() {
		return apply_filters( 'automatewoo/email/tracking_opt_out_text', __( 'Opt out of tracking', 'automatewoo' ), $this, $this->workflow );
	}

	/**
	 * The tracking opt-out link, as HTML.
	 *
	 * @since x.x.x
	 *
	 * @return bool|string
	 */
	public function get_tracking_opt_out_link() {
		$url  = $this->get_tracking_opt_out_url();
		$text = $this->get_tracking_opt_out_text();

		if ( ! $url || ! $text ) {
			return false;
		}

		return '<a href="' . esc_url( $url ) . '" class="automatewoo-tracking-opt-out-link" target="_blank" rel="noopener noreferrer">' . esc_html( $text ) . '</a>';
	}

	/**
	 * Every automatic footer link for this email, joined.
	 *
	 * @since x.x.x
	 *
	 * @return bool|string
	 */
	public function get_footer_links() {
		$links = array_filter(
			[
				$this->get_unsubscribe_link(),
				$this->get_tracking_opt_out_link(),
			]
		);

		if ( ! $links ) {
			return false;
		}

		$separator = apply_filters( 'automatewoo/email/footer_links_separator', ' | ', $this );

		return implode( $separator, $links );
	}

	/**
	 * Get the plain text unsubscribe footer.
	 *
	 * Will return false if workflow is transactional.
	 *
	 * @since 4.4.0
	 *
	 * @return bool|string
	 */
	public function get_plain_text_unsubscribe_footer() {
		$url  = $this->get_unsubscribe_url();
		$text = $this->get_unsubscribe_text();

		if ( ! $url || ! $text ) {
			return false;
		}

		return apply_filters( 'automatewoo/email/plain_text_unsubscribe_footer', "\n\n$text - $url", $this );
	}

	/**
	 * Get the plain text tracking opt-out footer.
	 *
	 * @since x.x.x
	 *
	 * @return bool|string
	 */
	public function get_plain_text_tracking_opt_out_footer() {
		$url  = $this->get_tracking_opt_out_url();
		$text = $this->get_tracking_opt_out_text();

		if ( ! $url || ! $text ) {
			return false;
		}

		return apply_filters( 'automatewoo/email/plain_text_tracking_opt_out_footer', "\n\n$text - $url", $this );
	}

	/**
	 * Get the email content with the plain text footer added.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_content_with_appended_plain_text_footer() {
		$content = $this->content;

		foreach ( [ $this->get_plain_text_unsubscribe_footer(), $this->get_plain_text_tracking_opt_out_footer() ] as $footer ) {
			if ( $footer ) {
				$content .= $footer;
			}
		}

		return $content;
	}


	/**
	 * @param string $url
	 * @return string
	 */
	public function replace_content_urls_callback( $url ) {
		if ( ! Tracking::is_url_excluded_from_click_tracking( $url ) ) {
			$url = html_entity_decode( $url );
			$url = $this->workflow->append_ga_tracking_to_url( $url );
			$url = Tracking::get_click_tracking_url( $this->workflow, $url );
		}

		return 'href="' . esc_url( $url ) . '"';
	}

	/**
	 * Callback for replacing bare URLs in plain text email content with tracked URLs.
	 *
	 * Unlike replace_content_urls_callback(), this uses esc_url_raw() instead of
	 * esc_url() so that ampersands in the tracking URL are not HTML-encoded (e.g. to
	 * &#038;), which would corrupt the URL in a plain text email body.
	 *
	 * @since 6.6.0
	 * @param string $url
	 * @return string
	 */
	public function replace_plain_text_url_callback( string $url ): string {
		if ( ! Tracking::is_url_excluded_from_click_tracking( $url ) ) {
			$url = html_entity_decode( $url );
			$url = $this->workflow->append_ga_tracking_to_url( $url );
			$url = Tracking::get_click_tracking_url( $this->workflow, $url );
		}

		return esc_url_raw( $url );
	}

	/**
	 * Replace bare URLs in plain text email content with tracked URLs.
	 *
	 * @since 6.6.0
	 * @param string $content Email content.
	 *
	 * @return string
	 */
	private function replace_plain_text_urls( $content ) {
		$replacer = new Replace_Helper( $content, [ $this, 'replace_plain_text_url_callback' ], 'text_urls' );
		return $replacer->process();
	}


	/**
	 * @return bool|\WP_Error
	 */
	public function send() {

		$mailer = $this->get_mailer();

		if ( ! $this->workflow ) {
			return new \WP_Error( 'workflow_blank', __( 'Workflow was not defined for email.', 'automatewoo' ) );
		}

		// validate email before checking if unsubscribed
		$validate_email = $mailer->validate_recipient_email();

		if ( is_wp_error( $validate_email ) ) {
			return $validate_email;
		}

		$customer = Customer_Factory::get_by_email( $this->recipient );

		if ( $this->workflow->is_customer_unsubscribed( $customer ) ) {
			return new \WP_Error( 'email_unsubscribed', __( 'The recipient is not opted-in to this workflow.', 'automatewoo' ) );
		}

		if ( ! $this->workflow->is_transactional() ) {
			$mailer->set_one_click_unsubscribe( Frontend::get_communication_page_permalink( $customer, 'unsubscribe', $this->workflow->get_id() ) );
		}

		\AW_Mailer_API::setup( $mailer, $this->workflow );

		$sent = $mailer->send();

		\AW_Mailer_API::cleanup();

		return $sent;
	}


	/**
	 * This method is currently only used when previewing.
	 *
	 * @return string
	 */
	public function get_email_body() {
		$mailer = $this->get_mailer();
		\AW_Mailer_API::setup( $mailer, $this->workflow );
		$html = $mailer->get_email_body();
		\AW_Mailer_API::cleanup();
		return $html;
	}
}

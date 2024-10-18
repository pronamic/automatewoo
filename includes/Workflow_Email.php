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

	/** @var string */
	public $template;

	/** @var bool */
	protected $tracking_enabled = false;

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
	 * @return Mailer|Mailer_Raw_HTML|Mailer_Plain_Text
	 */
	public function get_mailer() {

		if ( $this->is_type( 'plain-text' ) ) {
			$mailer = new Mailer_Plain_Text();
			$mailer->set_content( $this->get_content_with_appended_plain_text_footer() );
		} else {
			if ( $this->is_type( 'html-raw' ) ) {
				$mailer = new Mailer_Raw_HTML();
			} else {
				$mailer = new Mailer();
				$mailer->set_template( $this->template );
				$mailer->set_heading( $this->heading );
				$mailer->set_preheader( $this->preheader );
				$mailer->extra_footer_text = $this->get_unsubscribe_link();
			}

			$allowed_html          = wp_kses_allowed_html( 'post' );
			$allowed_html['style'] = array();

			$mailer->set_content( wp_kses( $this->content, $allowed_html ) );
			$mailer->set_include_automatewoo_styles( $this->include_automatewoo_styles );

			if ( $this->tracking_enabled ) {
				$mailer->tracking_pixel_url            = Tracking::get_open_tracking_url( $this->workflow );
				$mailer->replace_content_urls_callback = [ $this, 'replace_content_urls_callback' ];
			}
		}

		$mailer->set_subject( $this->subject );
		$mailer->set_email( $this->recipient );
		$mailer->reply_to = $this->reply_to;

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
	 * Get the email content with the plain text footer added.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_content_with_appended_plain_text_footer() {
		$footer = $this->get_plain_text_unsubscribe_footer();
		if ( $footer ) {
			return $this->content . $footer;
		}
		return $this->content;
	}


	/**
	 * @param string $url
	 * @return string
	 */
	public function replace_content_urls_callback( $url ) {
		if ( ! strstr( $url, 'aw-action=unsubscribe' ) ) {
			$url = html_entity_decode( $url );
			$url = $this->workflow->append_ga_tracking_to_url( $url );
			$url = Tracking::get_click_tracking_url( $this->workflow, $url );
		}

		return 'href="' . esc_url( $url ) . '"';
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
			$mailer->set_one_click_unsubscribe( Frontend::get_communication_page_permalink( $customer, 'unsubscribe' ) );
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

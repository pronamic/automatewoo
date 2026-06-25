<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

use Pelago\Emogrifier;
use Pelago\Emogrifier\CssInliner;
use Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner as WC_CssInliner;

/**
 * Mailer class for HTML emails that use a template.
 */
class Mailer extends Mailer_Abstract {

	/** @var string */
	public $template = 'default';

	/** @var string */
	public $heading;

	/** @var string */
	public $preheader;

	/** @var string */
	public $extra_footer_text;

	/** @var string */
	public $tracking_pixel_url;

	/** @var callable - use to replace URLs in content e.g. for click tracking */
	public $replace_content_urls_callback;

	/** @var bool */
	public $include_automatewoo_styles = true;


	/**
	 * Mailer constructor.
	 *
	 * All params are deprecated, use setter methods instead.
	 *
	 * @todo remove params, no longer in use after Refer A Friend 2.3
	 *
	 * @param string|false $subject
	 * @param string|false $email
	 * @param string|false $content
	 * @param string       $template
	 */
	public function __construct( $subject = false, $email = false, $content = false, $template = 'default' ) {

		// deprecated
		$this->email    = $email;
		$this->subject  = $subject;
		$this->content  = $content;
		$this->template = $template;

		do_action( 'automatewoo/mailer/init' );
	}


	/**
	 * @param string $heading
	 */
	public function set_heading( $heading ) {
		$this->heading = $heading;
	}


	/**
	 * @param string $preheader
	 */
	public function set_preheader( $preheader ) {
		$this->preheader = $preheader;
	}


	/**
	 * @param string $template
	 */
	public function set_template( $template ) {
		$this->template = $template;

		// Must reset from props after template is changed.
		$this->from_email = null;
		$this->from_name  = null;
	}


	/**
	 * @param bool $include
	 */
	public function set_include_automatewoo_styles( $include ) {
		$this->include_automatewoo_styles = $include;
	}


	/**
	 * Get email sender email address.
	 *
	 * @return string
	 */
	public function get_from_email() {
		if ( ! isset( $this->from_email ) ) {
			$this->from_email = Emails::get_from_address( $this->template );
		}
		return $this->from_email;
	}


	/**
	 * Get email sender name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		if ( ! isset( $this->from_name ) ) {
			$this->from_name = Emails::get_from_name( $this->template );
		}
		return $this->from_name;
	}


	/**
	 * Returns email body, can be HTML or plain text.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_email_body() {
		$html = $this->get_content_wrapped_in_template();
		return apply_filters( 'woocommerce_mail_content', $this->prepare_html( $html ) );
	}


	/**
	 * @return string
	 */
	public function get_content_wrapped_in_template() {
		$content                     = $this->content;
		$use_woocommerce_email_hooks = $this->should_use_woocommerce_email_hooks();

		add_filter( 'woocommerce_email_footer_text', [ $this, 'add_extra_footer_text' ] );

		/**
		 * @hooked wpautop()
		 */
		$content = apply_filters( 'automatewoo_email_content', $content );

		if ( $use_woocommerce_email_hooks ) {
			$this->maybe_init_woocommerce_mailer();

			// MailPoet's footer renders its own content and never applies the
			// 'woocommerce_email_footer_text' filter, so the extra footer text
			// (e.g. the unsubscribe link) would be silently dropped. Append it
			// directly after the content instead, and remove the filter so it
			// can't be added twice if the footer does apply it.
			remove_filter( 'woocommerce_email_footer_text', [ $this, 'add_extra_footer_text' ] );

			if ( $this->extra_footer_text ) {
				$content .= '<p>' . $this->extra_footer_text . '</p>';
			}
		}

		// Buffer
		ob_start();

		if ( $use_woocommerce_email_hooks ) {
			// Match WooCommerce's own invocation in WC_Emails::wrap_message(), which passes a
			// second (email) argument. Omitting it can trigger an ArgumentCountError for callbacks
			// registered with accepted_args that declare a required parameter.
			do_action( 'woocommerce_email_header', $this->heading, null );
		} else {
			$this->get_template_part(
				'email-header.php',
				[
					'email_heading' => $this->heading,
				]
			);
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;

		if ( $use_woocommerce_email_hooks ) {
			// Match WooCommerce's own invocation in WC_Emails::wrap_message(), which passes an
			// (email) argument. Omitting it can trigger an ArgumentCountError for callbacks
			// registered with accepted_args that declare a required parameter.
			do_action( 'woocommerce_email_footer', null );
		} else {
			$this->get_template_part( 'email-footer.php' );
		}

		$html = ob_get_clean();

		remove_filter( 'woocommerce_email_footer_text', [ $this, 'add_extra_footer_text' ] );

		return $html;
	}

	/**
	 * Determine whether the default template should render through WooCommerce's email hooks.
	 *
	 * MailPoet replaces these hooks when its WooCommerce email editor is enabled.
	 *
	 * @return bool
	 */
	protected function should_use_woocommerce_email_hooks() {
		return 'default' === $this->template && Integrations::is_mailpoet_overriding_styles();
	}

	/**
	 * Ensure WooCommerce's email header and footer hooks have been registered.
	 */
	protected function maybe_init_woocommerce_mailer() {
		if ( function_exists( 'WC' ) ) {
			$woocommerce = WC();

			if ( is_callable( [ $woocommerce, 'mailer' ] ) ) {
				$woocommerce->mailer();
			}
		}
	}


	/**
	 * Prepare HTML before it's sent.
	 * Should be run after dynamic content like variables have been inserted.
	 *
	 * - Maybe injects preheader
	 * - Processes email variables like {{ unsubscribe_url }}
	 * - Fixes links with double http
	 * - Maybe convert URLs to trackable URLs
	 * - Replaces unsupported HTML tags
	 * - Runs wptexturize() to convert quotes
	 * - Fix container ID for MailPoet compatibility if required
	 * - HTML encodes emojis
	 * - Injects tracking pixel
	 * - Inlines CSS
	 *
	 * @since 4.3.0
	 *
	 * @param string $html
	 * @return string
	 */
	public function prepare_html( $html ) {
		if ( $this->preheader ) {
			$html = $this->inject_preheader( $html );
		}

		$html = $this->process_email_variables( $html );
		$html = $this->fix_links_with_double_http( $html );
		$html = $this->replace_urls_in_content( $html );
		$html = $this->wptexturize_preserve_urls( $html );

		// If MailPoet is active and customizing WooCommerce emails then the container ID needs to be updated for compatibility
		if ( Integrations::is_mailpoet_overriding_styles() ) {
			$html = $this->fix_wrapper_for_mailpoet( $html );
		}

		$html = $this->style_inline( $html );
		$html = Clean::encode_emoji( $html ); // encoding emojis before CSS inline seems to decode them again

		if ( $this->tracking_pixel_url ) {
			$html = $this->inject_tracking_pixel( $html ); // add tracking pixel after CSS inline
		}

		return $html;
	}

	/**
	 * Run wptexturize() on HTML while preserving URLs in text nodes.
	 *
	 * WordPress's wptexturize() converts character sequences like '--' to typographic
	 * equivalents (e.g. en-dash '–'). While it correctly skips HTML tag attributes,
	 * it still processes text nodes which may contain bare URLs. This is problematic
	 * for WooCommerce HPOS admin URLs that contain '--' (e.g. 'wc-orders--shop_subscription').
	 *
	 * @param string $html
	 * @return string
	 */
	private function wptexturize_preserve_urls( $html ) {
		$placeholders = [];
		$counter      = 0;

		// Replace URLs in text nodes (not inside HTML tags) with placeholders.
		$html = preg_replace_callback(
			// Match URLs that are NOT inside HTML tag attributes.
			// This regex splits the HTML into tags and text, and only processes text portions.
			'/(<[^>]*>)|(\bhttps?:\/\/[^\s<>"\']+)/i',
			function ( $matches ) use ( &$placeholders, &$counter ) {
				// If this is an HTML tag, leave it unchanged.
				if ( ! empty( $matches[1] ) ) {
					return $matches[0];
				}

				// This is a URL in a text node — replace with placeholder.
				$placeholder                  = '{{AW_URL_PLACEHOLDER_' . $counter . '}}';
				$placeholders[ $placeholder ] = $matches[0];
				++$counter;
				return $placeholder;
			},
			$html
		);

		$html = wptexturize( $html );

		// Restore original URLs.
		if ( $placeholders ) {
			$html = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $html );
		}

		return $html;
	}

	/**
	 * Replace default email wrapper ID with one required for MailPoet inline styling
	 *
	 * @param string $html The contents of the email
	 * @return string
	 */
	public function fix_wrapper_for_mailpoet( $html ) {
		return str_replace( 'id="wrapper"', 'id="mailpoet_woocommerce_container"', $html );
	}


	/**
	 * Fix any duplicate http in links, can happen due to variables
	 *
	 * @param string $content
	 * @return string
	 */
	public function fix_links_with_double_http( $content ) {
		$content = str_replace( '"http://http://', '"http://', $content );
		$content = str_replace( '"https://https://', '"https://', $content );
		$content = str_replace( '"http://https://', '"https://', $content );
		$content = str_replace( '"https://http://', '"http://', $content );
		return $content;
	}


	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @param string|null $content
	 * @return string
	 */
	public function style_inline( $content ) {
		ob_start();

		if ( $this->include_automatewoo_styles ) {
			aw_get_template( 'email/styles.php' );
		}

		$this->get_template_part( 'email-styles.php' );
		$css = apply_filters( 'woocommerce_email_styles', ob_get_clean(), new \WC_Email() );
		$css = apply_filters( 'automatewoo/mailer/styles', $css, $this );

		return $this->emogrify( $content, $css );
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function add_extra_footer_text( $text ) {

		if ( ! $this->extra_footer_text ) {
			return $text;
		}

		// add separator if there is footer text
		if ( trim( $text ) ) {
			$text .= apply_filters( 'automatewoo_email_footer_separator', ' - ' );
		}

		$text .= $this->extra_footer_text;

		return $text;
	}


	/**
	 * Get a template part.
	 *
	 * @param string $file_name The name of the template file.
	 * @param array  $variables Array of variables for use in the template file.
	 */
	public function get_template_part( $file_name, $variables = [] ) {
		switch ( $this->template ) {

			// default is the woocommerce template
			case 'default':
				$template_name = 'emails/' . $file_name;
				$template_path = '';
				break;

			// 'plain' doesn't mean the email is plain text
			case 'plain':
				aw_get_template( 'email/plain/' . $file_name, $variables );
				return;

			// Custom template
			default:
				$template_data = Emails::get_template( $this->template );
				$template_name = $file_name;

				// Check if this template has a custom path
				if ( is_array( $template_data ) && isset( $template_data['path'] ) ) {
					$template_path = untrailingslashit( $template_data['path'] );
				} else {
					$template_path = 'automatewoo/custom-email-templates/' . $this->template;
				}
				break;
		}

		if ( aw_str_starts_with( $template_path, '/' ) ) {
			// Path is absolute
			$located = $template_path . '/' . $template_name;
		} else {
			// Locate the relative path template
			$located = wc_locate_template( $template_name, $template_path );
		}

		// If using the woo default template, apply filters to support email customizer plugins
		if ( 'default' === $this->template ) {
			$located = apply_filters( 'wc_get_template', $located, $template_name, $variables, $template_path, '' );
			do_action( 'woocommerce_before_template_part', $template_name, $template_path, $located, $variables );
		}

		$this->load_template_part( $located, $variables );

		if ( 'default' === $this->template ) {
			do_action( 'woocommerce_after_template_part', $template_name, $template_path, $located, $variables );
		}
	}

	/**
	 * Load a template part if it's found.
	 *
	 * Prefix params with '_' to prevent clashes when using extract on $_variables.
	 *
	 * @since 4.8.0
	 *
	 * @param string $_template_file
	 * @param array  $_variables
	 */
	public function load_template_part( $_template_file, $_variables ) {
		if ( is_array( $_variables ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $_variables, EXTR_SKIP );
		}

		if ( $_template_file && file_exists( $_template_file ) ) {
			include $_template_file; // nosemgrep This has not reached user input. Is used in Mailer.php to load the templates
		}
	}

	/**
	 * Maybe replace URLs with trackable URLs
	 *
	 * @param string $content
	 * @return string
	 */
	public function replace_urls_in_content( $content ) {
		if ( ! $this->replace_content_urls_callback ) {
			return $content;
		}

		$replacer = new Replace_Helper( $content, $this->replace_content_urls_callback, 'href_urls' );
		return $replacer->process();
	}


	/**
	 * Injects preheader HTML after opening <body> tag
	 *
	 * @param string $html
	 * @return string
	 */
	public function inject_preheader( $html ) {
		return preg_replace_callback(
			'/<body[^>]*>/',
			function ( $matches ) {
				$preheader = '<div class="automatewoo-email-preheader" style="display: none !important; font-size: 1px;">' . esc_html( $this->preheader ) . '</div>';
				return $matches[0] . $preheader;
			},
			$html,
			1
		);
	}


	/**
	 * Injects tracking pixel before closing </body> tag
	 *
	 * @param string $html
	 * @return string
	 */
	public function inject_tracking_pixel( $html ) {
		return preg_replace_callback(
			'/<\/body[^>]*>/',
			function ( $matches ) {
				return $this->get_tracking_pixel_img() . $matches[0];
			},
			$html,
			1
		);
	}


	/**
	 * @return string
	 */
	public function get_tracking_pixel_img() {
		return '<img src="' . esc_url( $this->tracking_pixel_url ) . '" height="1" width="1" alt="" style="display:inline">';
	}


	/**
	 * Add inline CSS to HTML with the Emogrifier library.
	 *
	 * If Emogrifier can't be used the unmodified HTML will be returned.
	 *
	 * @since 4.4.2
	 *
	 * @param string $html                    The HTML.
	 * @param string $css                     The CSS to be inlined.
	 * @param bool   $parse_html_style_blocks Should CSS in HTML style blocks also be inlined?
	 *
	 * @return string
	 */
	public function emogrify( $html, $css, $parse_html_style_blocks = false ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		// WooCommerce 10.4+ uses a namespaced WC_CssInliner to prevent conflicts with other plugins.
		// Fall back to CssInliner, then the deprecated Emogrifier class for older WooCommerce versions.
		if ( class_exists( WC_CssInliner::class ) ) {
			$emogrifier = WC_CssInliner::fromHtml( $html );
		} elseif ( class_exists( CssInliner::class ) ) {
			$emogrifier = CssInliner::fromHtml( $html );
		} elseif ( class_exists( Emogrifier::class ) ) {
			$emogrifier = new Emogrifier( $html, $css );
		} else {
			return $html;
		}

		try {
			if ( ! $parse_html_style_blocks ) {
				$emogrifier->disableStyleBlocksParsing();
			}

			/*
			 * The disableInvisibleNodeRemoval() method was removed with version 3+ of Emogrifier,
			 * which was included in WC 4.0+. Disabling the removal of invisible nodes is now
			 * default behavior, so we don't need to do anything differently if the method
			 * cannot be found.
			 */
			if ( method_exists( $emogrifier, 'disableInvisibleNodeRemoval' ) ) {
				$emogrifier->disableInvisibleNodeRemoval();
			}

			if ( $emogrifier instanceof WC_CssInliner || $emogrifier instanceof CssInliner ) {
				$html = $emogrifier->inlineCss( $css )->render();
			} else {
				$html = $emogrifier->emogrify();
			}
		} catch ( \Exception $e ) {
			Logger::error( 'emogrifier', $e->getMessage() );
		}

		return $html;
	}
}

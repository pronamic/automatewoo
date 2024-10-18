<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Mailer_Raw_HTML
 * @since 3.6.0
 */
class Mailer_Raw_HTML extends Mailer {


	/**
	 * Inline styles already contained in the HTML
	 *
	 * @param string|null $content
	 * @return string
	 */
	public function style_inline( $content ) {
		$css = '';

		if ( $this->include_automatewoo_styles ) {
			ob_start();
			aw_get_template( 'email/styles.php' );
			$css = ob_get_clean();
		}

		$css = apply_filters( 'automatewoo/mailer_raw/styles', $css, $this );

		return $this->emogrify( $content, $css, true );
	}


	/**
	 * @return string
	 */
	public function get_email_body() {
		$html = $this->content;
		return $this->prepare_html( $html );
	}
}

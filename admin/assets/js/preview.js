// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global ajaxurl, alert, automatewooPreviewLocalizeScript */
jQuery( function ( $ ) {
	function setIframeHeight() {
		$( '.aw-preview__email-iframe' ).height(
			$( window ).height() - $( '.aw-preview__header' ).outerHeight()
		);
	}

	$( 'form.aw-preview__send-test-form' ).on( 'submit', function ( e ) {
		e.preventDefault();

		const $form = $( this );

		$form.addClass( 'aw-loading' );
		$form.find( 'button' ).trigger( 'blur' );

		const data = {
			action: 'aw_send_test_email',
			type: $form.find( '[name="type"]' ).val(),
			to_emails: $form.find( '[name="to_emails"]' ).val(),
			args: JSON.parse( $form.find( '[name="args"]' ).val() ),
			nonce: automatewooPreviewLocalizeScript.nonce,
		};

		$.post( ajaxurl, data, function ( response ) {
			// eslint-disable-next-line no-alert -- Pre eslint introduction code, to be revised.
			alert( response.data.message );
			$form.removeClass( 'aw-loading' );
		} );

		return false;
	} );

	/**
	 * Init
	 */
	function init() {
		setIframeHeight();

		$( window ).on( 'resize', function () {
			setIframeHeight();
		} );
	}

	init();
} );

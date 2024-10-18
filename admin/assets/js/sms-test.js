// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AutomateWoo, ajaxurl, automatewooSmsTestLocalizeScript */

/**
 * AutomateWoo SMS Tester
 */

jQuery( function ( $ ) {
	AutomateWoo.SMS_Tester = {
		$fields: {
			from: $( '#automatewoo_twilio_from' ),
			auth_id: $( '#automatewoo_twilio_auth_id' ),
			auth_token: $( '#automatewoo_twilio_auth_token' ),
			test_recipient: $( '#automatewoo-sms-test-recipient' ),
			test_message: $( '#automatewoo-sms-test-message' ),
		},

		$button: $( '#automatewoo-sms-test-twilio' ),

		init() {
			AutomateWoo.SMS_Tester.$button.on( 'click', function () {
				AutomateWoo.SMS_Tester.send_test();
			} );
		},

		send_test() {
			const textInitial = AutomateWoo.SMS_Tester.$button.val(),
				textLoading =
					AutomateWoo.SMS_Tester.$button.data( 'loading-text' );

			AutomateWoo.SMS_Tester.$button
				.val( textLoading )
				.addClass( 'disabled' )
				.trigger( 'blur' );

			AutomateWoo.notices.clear_all();

			$.ajax( {
				method: 'POST',
				url: ajaxurl,
				data: {
					action: 'aw_test_sms',
					from: AutomateWoo.SMS_Tester.$fields.from.val(),
					auth_id: AutomateWoo.SMS_Tester.$fields.auth_id.val(),
					auth_token: AutomateWoo.SMS_Tester.$fields.auth_token.val(),
					test_message:
						AutomateWoo.SMS_Tester.$fields.test_message.val(),
					test_recipient:
						AutomateWoo.SMS_Tester.$fields.test_recipient.val(),
					nonce: automatewooSmsTestLocalizeScript.nonce,
				},
			} )
				.done( function ( response ) {
					// eslint-disable-next-line no-console -- Pre eslint introduction code, to be revised.
					console.log( response );

					if ( response.success ) {
						AutomateWoo.notices.success(
							response.data.message,
							$( '.automatewoo-sms-test-container' )
						);
					} else {
						AutomateWoo.notices.error(
							response.data.message,
							$( '.automatewoo-sms-test-container' )
						);
					}

					AutomateWoo.SMS_Tester.$button
						.val( textInitial )
						.removeClass( 'disabled' );
				} )

				.fail( function ( response ) {
					// eslint-disable-next-line no-console -- Pre eslint introduction code, to be revised.
					console.log( response );
				} );
		},
	};

	AutomateWoo.SMS_Tester.init();
} );

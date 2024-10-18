// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AW, automatewooSettingsLocalizeScript, alert */
( function ( $ ) {
	const self = {
		params: {},

		init() {
			self.params = automatewooSettingsLocalizeScript;
			self.initSwitchToOptinModeWarning();
		},

		initSwitchToOptinModeWarning() {
			const $field = $( '#automatewoo_optin_mode' );

			$field.on( 'change', function () {
				if ( $( this ).val() === 'optin' ) {
					// eslint-disable-next-line no-alert -- Pre eslint introduction code, to be revised.
					alert( self.params.messages.switchToOptinWarning );
				}
			} );
		},
	};

	AW.Settings = self;
	self.init();
} )( jQuery );

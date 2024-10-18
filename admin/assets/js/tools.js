// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AutomateWoo, confirm */
/**
 * AutomateWoo Tools
 */

jQuery( function () {
	AutomateWoo.Tools = {
		init() {
			// $('#automatewoo_process_tool_form').on( 'submit', this.confirm_submit );
		},

		confirm_submit() {
			// eslint-disable-next-line no-alert -- Pre eslint introduction code, to be revised.
			return confirm( 'Are you sure? This can not be undone.' );
		},
	};

	AutomateWoo.Tools.init();
} );

// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AutomateWoo, AW */
/**
 * AutomateWoo Modal
 */
AutomateWoo.Modal = {
	/**
	 * A set of classes to be used to interact with the modal singleton.
	 */
	triggerClasses: {
		/** Clicking on such element closes the modal. */
		close: 'js-close-automatewoo-modal',
		/**
		 * To be used on `HTMLAnchorElement`, to load ajax content fetched from `href`.
		 */
		openLink: 'js-open-automatewoo-modal',
	},
};
jQuery( function ( $ ) {
	Object.assign( AutomateWoo.Modal, {
		init() {
			const $body = $( document.body );
			$body.on( 'click', `.${ this.triggerClasses.close }`, () => {
				this.close( 'dismiss' );
			} );
			$body.on( 'click', '.automatewoo-modal-overlay', () => {
				this.close( 'dismiss' );
			} );
			$body.on(
				'click',
				`.${ this.triggerClasses.openLink }`,
				this.handle_link
			);

			$( document ).on( 'keydown', function ( e ) {
				if ( e.keyCode === 27 ) {
					AutomateWoo.Modal.close( 'dismiss' );
				}
			} );
		},

		handle_link( e ) {
			e.preventDefault();

			const $a = $( this );
			const size = $a.data( 'automatewoo-modal-size' );

			AutomateWoo.Modal.open( size );
			AutomateWoo.Modal.loading();

			$.post( $a.attr( 'href' ), {}, function ( response ) {
				AutomateWoo.Modal.contents( response );
			} );
		},

		open( size ) {
			let sizeClass = '';

			if ( size ) {
				sizeClass = 'automatewoo-modal--size-' + size;
			}

			document.body.classList.add( 'automatewoo-modal-open' );

			$( document.body ).append(
				`<div class="automatewoo-modal-container"><div class="automatewoo-modal-overlay"></div><div class="automatewoo-modal  ${ sizeClass }"><div class="automatewoo-modal__contents"><div class="automatewoo-modal__header"></div></div><div class="automatewoo-icon-close ${ this.triggerClasses.close }"></div></div></div>`
			);
		},

		loading() {
			document.body.classList.add( 'automatewoo-modal-loading' );
		},

		contents( contents ) {
			document.body.classList.remove( 'automatewoo-modal-loading' );
			$( '.automatewoo-modal__contents' ).html( contents );

			AW.initTooltips();
		},

		/**
		 * Closes modal, by changin classes on `document.body` and removing modal elements.
		 *
		 * @param {string} [closedBy=''] - Identifier for what closed the modal which will be included in the event detail.
		 *
		 * @fires CustomEvent with event name 'awmodal-close' on the `document.body`.
		 */
		close( closedBy = '' ) {
			document.body.classList.remove(
				'automatewoo-modal-open',
				'automatewoo-modal-loading'
			);
			$( '.automatewoo-modal-container' ).remove();

			const detail = { closedBy };
			const modalClose = new CustomEvent( 'awmodal-close', { detail } );

			document.body.dispatchEvent( modalClose );
		},
	} );

	AutomateWoo.Modal.init();
} );

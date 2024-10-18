( function ( $ ) {
	/**
	 * Re-inits TinyMCE editors when a workflow metabox is moved.
	 */
	const reInitTinyMceOnMetaboxMove = () => {
		const reset = () => {
			tinymce.remove();
			Object.values( tinyMCEPreInit.mceInit ).forEach( ( editor ) => {
				tinymce.init( editor );
			} );
		};

		$( '#normal-sortables' ).on( 'sortupdate', function () {
			reset();
		} );

		const $orderButtons = $(
			'.postbox .handle-order-higher, .postbox .handle-order-lower'
		);
		$orderButtons.on( 'click.postboxes', function () {
			setTimeout( function () {
				reset();
			}, 100 );
		} );
	};

	if ( document.body.classList.contains( 'post-type-aw_workflow' ) ) {
		reInitTinyMceOnMetaboxMove();
	}
} )( window.jQuery );

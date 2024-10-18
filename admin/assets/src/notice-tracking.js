/**
 * External dependencies
 */
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_PREFIX } from './settings';

( function ( $ ) {
	/**
	 * Adds tracking for admin notices
	 */
	const initAdminNoticeTracking = () => {
		const $notice = $( '.notice[data-automatewoo-dismissible-notice]' );

		$notice.each( function () {
			const noticeIdentifier = $( this )
				.data( 'automatewoo-dismissible-notice' )
				.replace( '-', '_' );

			recordEvent( TRACKS_PREFIX + 'notice_viewed', {
				notice_identifier: noticeIdentifier,
			} );

			$( this ).on(
				'click',
				'a[data-automatewoo-link-type]',
				function () {
					queueRecordEvent( TRACKS_PREFIX + 'notice_link_clicked', {
						notice_identifier: noticeIdentifier,
						link_type: $( this ).data( 'automatewoo-link-type' ),
					} );
					return true;
				}
			);
			$( this ).on( 'click', 'button.notice-dismiss', function () {
				recordEvent( TRACKS_PREFIX + 'notice_dismissed', {
					notice_identifier: noticeIdentifier,
				} );
				return true;
			} );
		} );
	};

	if (
		document.querySelector( '.notice[data-automatewoo-dismissible-notice]' )
	) {
		initAdminNoticeTracking();
	}
} )( window.jQuery );

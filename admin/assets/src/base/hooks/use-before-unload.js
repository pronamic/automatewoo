/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * useBeforeUnload hook.
 *
 * Most browser will replace the custom before unload message.
 *
 * @param {string} message The message to show before unload.
 *                         If empty string no message will be shown.
 */
export const useBeforeUnload = ( message ) => {
	useEffect( () => {
		const eventListener = ( event ) => {
			if ( message ) {
				event.returnValue = message;
				return event.returnValue;
			}
		};
		window.addEventListener( 'beforeunload', eventListener );
		return () => {
			window.removeEventListener( 'beforeunload', eventListener );
		};
	}, [ message ] );
};

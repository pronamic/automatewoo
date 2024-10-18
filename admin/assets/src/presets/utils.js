/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_PREFIX } from '../settings';

export const recordPresetListButtonClickTracksEvent = (
	action,
	presetName
) => {
	recordEvent( TRACKS_PREFIX + 'preset_list_button_clicked', {
		action,
		preset_name: presetName,
	} );
};

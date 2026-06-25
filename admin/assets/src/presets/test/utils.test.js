/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { recordPresetListButtonClickTracksEvent } from '../utils';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

// settings.js reads from the global `wc.wcSettings` object at import time,
// which is not available in the test environment, so stub the prefix.
jest.mock( '../../settings', () => ( {
	TRACKS_PREFIX: 'aw_',
} ) );

describe( 'recordPresetListButtonClickTracksEvent', () => {
	beforeEach( () => {
		recordEvent.mockClear();
	} );

	it( 'records a prefixed tracks event with the action and preset name', () => {
		recordPresetListButtonClickTracksEvent( 'create', 'abandoned-cart' );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'aw_preset_list_button_clicked',
			{
				action: 'create',
				preset_name: 'abandoned-cart',
			}
		);
	} );

	it( 'passes through whatever action and preset name are given', () => {
		recordPresetListButtonClickTracksEvent( 'guide', 'win-back' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'aw_preset_list_button_clicked',
			{
				action: 'guide',
				preset_name: 'win-back',
			}
		);
	} );
} );

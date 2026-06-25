/**
 * Internal dependencies
 */
import { getPresets } from '../resolvers';
import TYPES from '../action-types';

describe( 'presets resolvers', () => {
	describe( 'getPresets', () => {
		it( 'sets requesting, fetches presets and dispatches updatePresets on success', () => {
			const gen = getPresets();

			// First yield: mark getPresets as requesting.
			expect( gen.next().value ).toEqual( {
				type: TYPES.SET_IS_REQUESTING,
				selector: 'getPresets',
				isRequesting: true,
			} );

			// Second yield: the apiFetch control for the presets endpoint.
			const apiFetchControl = gen.next().value;
			// It must be an API_FETCH control descriptor, not a plain object,
			// so @wordpress/data-controls actually performs the request.
			expect( apiFetchControl.type ).toBe( 'API_FETCH' );
			expect( apiFetchControl.request ).toEqual( {
				path: '/automatewoo/presets',
				method: 'GET',
			} );

			// Resolve the apiFetch with a list of presets.
			const presets = [ { name: 'preset-1' } ];
			const updateAction = gen.next( presets ).value;
			expect( updateAction ).toEqual( {
				type: TYPES.UPDATE_PRESETS,
				presets,
			} );

			expect( gen.next().done ).toBe( true );
		} );

		it( 'dispatches setError when the request fails', () => {
			const gen = getPresets();

			gen.next(); // setIsRequesting
			gen.next(); // apiFetch control

			const apiError = new Error( 'could not load presets' );
			const errorAction = gen.throw( apiError ).value;

			expect( errorAction ).toEqual( {
				type: TYPES.SET_ERROR,
				selector: 'getPresets',
				error: apiError,
			} );

			// The resolver swallows the error and completes.
			expect( gen.next().done ).toBe( true );
		} );
	} );
} );

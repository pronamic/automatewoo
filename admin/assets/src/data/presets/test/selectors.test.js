/**
 * Internal dependencies
 */
import {
	getPresets,
	didCreateWorkflow,
	isRequesting,
	getError,
} from '../selectors';

describe( 'presets selectors', () => {
	describe( 'getPresets', () => {
		it( 'returns the presets array from state', () => {
			const presets = [ { name: 'a' }, { name: 'b' } ];

			expect( getPresets( { presets } ) ).toBe( presets );
		} );
	} );

	describe( 'didCreateWorkflow', () => {
		it( 'returns the didCreateWorkflow flag from state', () => {
			expect( didCreateWorkflow( { didCreateWorkflow: true } ) ).toBe(
				true
			);
			expect( didCreateWorkflow( { didCreateWorkflow: false } ) ).toBe(
				false
			);
		} );
	} );

	describe( 're-exported base selectors', () => {
		it( 'isRequesting returns the flag for a selector', () => {
			const state = { requesting: { getPresets: true } };

			expect( isRequesting( state, 'getPresets' ) ).toBe( true );
		} );

		it( 'isRequesting defaults to false for unknown selectors', () => {
			expect( isRequesting( { requesting: {} }, 'nope' ) ).toBe( false );
		} );

		it( 'getError returns the stored error', () => {
			const error = new Error( 'boom' );
			const state = { errors: { getPresets: error } };

			expect( getError( state, 'getPresets' ) ).toBe( error );
		} );

		it( 'getError defaults to false when there is no error', () => {
			expect( getError( { errors: {} }, 'getPresets' ) ).toBe( false );
		} );
	} );
} );

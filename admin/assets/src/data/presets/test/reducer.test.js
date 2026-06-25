/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

describe( 'presets reducer', () => {
	it( 'returns the default initial state', () => {
		const state = reducer( undefined, { type: 'NOOP' } );

		expect( state ).toEqual( {
			requesting: {},
			errors: {},
			presets: [],
			didCreateWorkflow: false,
		} );
	} );

	it( 'leaves state unchanged for an unknown action type', () => {
		const initial = {
			requesting: { getPresets: true },
			errors: {},
			presets: [ { name: 'a' } ],
			didCreateWorkflow: false,
		};

		const state = reducer( initial, { type: 'SOME_UNKNOWN_TYPE' } );

		expect( state ).toEqual( initial );
	} );

	describe( 'UPDATE_PRESETS', () => {
		it( 'stores the presets payload', () => {
			const presets = [ { name: 'preset-1' }, { name: 'preset-2' } ];

			const state = reducer( undefined, {
				type: TYPES.UPDATE_PRESETS,
				presets,
			} );

			expect( state.presets ).toEqual( presets );
		} );

		it( 'resets the requesting and error state for getPresets', () => {
			const initial = {
				requesting: { getPresets: true, other: true },
				errors: { getPresets: new Error( 'boom' ), other: 'keep' },
				presets: [],
				didCreateWorkflow: false,
			};

			const state = reducer( initial, {
				type: TYPES.UPDATE_PRESETS,
				presets: [ { name: 'x' } ],
			} );

			expect( state.requesting.getPresets ).toBe( false );
			expect( state.errors.getPresets ).toBe( false );
			// Unrelated keys are preserved.
			expect( state.requesting.other ).toBe( true );
			expect( state.errors.other ).toBe( 'keep' );
		} );

		it( 'does not mutate the previous state', () => {
			const initial = {
				requesting: {},
				errors: {},
				presets: [],
				didCreateWorkflow: false,
			};

			const state = reducer( initial, {
				type: TYPES.UPDATE_PRESETS,
				presets: [ { name: 'x' } ],
			} );

			expect( state ).not.toBe( initial );
			expect( initial.presets ).toEqual( [] );
		} );
	} );

	describe( 'CREATED_WORKFLOW', () => {
		it( 'sets didCreateWorkflow to true', () => {
			const state = reducer( undefined, {
				type: TYPES.CREATED_WORKFLOW,
			} );

			expect( state.didCreateWorkflow ).toBe( true );
		} );

		it( 'resets the requesting and error state for createWorkflow', () => {
			const initial = {
				requesting: { createWorkflow: true },
				errors: { createWorkflow: new Error( 'boom' ) },
				presets: [],
				didCreateWorkflow: false,
			};

			const state = reducer( initial, {
				type: TYPES.CREATED_WORKFLOW,
			} );

			expect( state.requesting.createWorkflow ).toBe( false );
			expect( state.errors.createWorkflow ).toBe( false );
			expect( state.didCreateWorkflow ).toBe( true );
		} );

		it( 'preserves existing presets', () => {
			const presets = [ { name: 'keep-me' } ];
			const initial = {
				requesting: {},
				errors: {},
				presets,
				didCreateWorkflow: false,
			};

			const state = reducer( initial, {
				type: TYPES.CREATED_WORKFLOW,
			} );

			expect( state.presets ).toEqual( presets );
		} );
	} );

	describe( 'base actions', () => {
		it( 'handles SET_IS_REQUESTING and clears the related error', () => {
			const initial = reducer( undefined, { type: 'NOOP' } );

			const state = reducer( initial, {
				type: TYPES.SET_IS_REQUESTING,
				selector: 'getPresets',
				isRequesting: true,
			} );

			expect( state.requesting.getPresets ).toBe( true );
			expect( state.errors.getPresets ).toBeNull();
		} );

		it( 'handles SET_ERROR and stops requesting', () => {
			const error = new Error( 'request failed' );
			const initial = reducer( undefined, { type: 'NOOP' } );

			const state = reducer( initial, {
				type: TYPES.SET_ERROR,
				selector: 'getPresets',
				error,
			} );

			expect( state.requesting.getPresets ).toBe( false );
			expect( state.errors.getPresets ).toBe( error );
		} );
	} );
} );

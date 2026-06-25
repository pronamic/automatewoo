/**
 * Internal dependencies
 */
import {
	updatePresets,
	receiveCreatedWorkflow,
	createWorkflow,
} from '../actions';
import TYPES from '../action-types';

describe( 'presets actions', () => {
	describe( 'updatePresets', () => {
		it( 'returns an UPDATE_PRESETS action with the presets payload', () => {
			const presets = [ { name: 'a' } ];

			expect( updatePresets( presets ) ).toEqual( {
				type: TYPES.UPDATE_PRESETS,
				presets,
			} );
		} );
	} );

	describe( 'receiveCreatedWorkflow', () => {
		it( 'returns a CREATED_WORKFLOW action', () => {
			expect( receiveCreatedWorkflow() ).toEqual( {
				type: TYPES.CREATED_WORKFLOW,
			} );
		} );
	} );

	describe( 'createWorkflow', () => {
		it( 'sets requesting, posts to the API and returns the workflow ID on success', () => {
			const gen = createWorkflow( 'abandoned-cart' );

			// First yield: mark the action as requesting.
			expect( gen.next().value ).toEqual( {
				type: TYPES.SET_IS_REQUESTING,
				selector: 'createWorkflow',
				isRequesting: true,
			} );

			// Second yield: the apiFetch control with the correct request.
			const apiFetchControl = gen.next().value;
			// It must be an API_FETCH control descriptor, not a plain object,
			// so @wordpress/data-controls actually performs the request.
			expect( apiFetchControl.type ).toBe( 'API_FETCH' );
			expect( apiFetchControl.request ).toEqual( {
				path: '/automatewoo/presets/create-workflow',
				method: 'POST',
				data: { preset_name: 'abandoned-cart' },
			} );

			// Resolve the apiFetch with a valid response.
			const receiveAction = gen.next( { workflow_id: 123 } ).value;
			expect( receiveAction ).toEqual( {
				type: TYPES.CREATED_WORKFLOW,
			} );

			// Generator returns the workflow ID and completes.
			const final = gen.next();
			expect( final.value ).toBe( 123 );
			expect( final.done ).toBe( true );
		} );

		it( 'sets an error and throws when the response has no workflow ID', () => {
			const gen = createWorkflow( 'abandoned-cart' );

			gen.next(); // setIsRequesting
			gen.next(); // apiFetch control

			// Respond with no workflow_id -> the generator throws internally
			// and yields a setError action.
			const errorAction = gen.next( {} ).value;
			expect( errorAction.type ).toBe( TYPES.SET_ERROR );
			expect( errorAction.selector ).toBe( 'createWorkflow' );
			expect( errorAction.error ).toBeInstanceOf( Error );

			// After yielding setError the generator re-throws.
			expect( () => gen.next() ).toThrow();
		} );

		it( 'sets an error and re-throws when apiFetch fails', () => {
			const gen = createWorkflow( 'abandoned-cart' );

			gen.next(); // setIsRequesting
			gen.next(); // apiFetch control

			const apiError = new Error( 'network down' );
			const errorAction = gen.throw( apiError ).value;

			expect( errorAction ).toEqual( {
				type: TYPES.SET_ERROR,
				selector: 'createWorkflow',
				error: apiError,
			} );

			// The original error is re-thrown to the caller.
			expect( () => gen.next() ).toThrow( apiError );
		} );
	} );
} );

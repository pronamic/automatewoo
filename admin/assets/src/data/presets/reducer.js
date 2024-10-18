/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { baseReducer, baseInitialState } from '../base/reducer';

/**
 * Sets the requesting and errors state to false for a given action name.
 *
 * @param {Object} state
 * @param {Object} actionName
 * @return {Object} containing updated requesting and error args.
 */
const resetRequestingAndErrorState = ( state, actionName ) => {
	return {
		requesting: {
			...state.requesting,
			[ actionName ]: false,
		},
		errors: {
			...state.errors,
			[ actionName ]: false,
		},
	};
};

const reducer = (
	state = {
		...baseInitialState,
		presets: [],
		didCreateWorkflow: false,
	},
	action
) => {
	const { type, presets } = action;

	switch ( type ) {
		case TYPES.UPDATE_PRESETS:
			state = {
				...state,
				...resetRequestingAndErrorState( state, 'getPresets' ),
				presets,
			};
			break;
		case TYPES.CREATED_WORKFLOW:
			state = {
				...state,
				...resetRequestingAndErrorState( state, 'createWorkflow' ),
				didCreateWorkflow: true,
			};
			break;
	}
	return baseReducer( state, action );
};

export default reducer;

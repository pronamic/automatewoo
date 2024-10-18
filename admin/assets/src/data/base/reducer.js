/**
 * Internal dependencies
 */
import TYPES from './action-types';

export const baseInitialState = {
	requesting: {},
	errors: {},
};

export const baseReducer = (
	state,
	{ type, selector, isRequesting, error }
) => {
	switch ( type ) {
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: isRequesting,
				},
				// Clear errors for the selector when new request starts
				errors: {
					...state.errors,
					[ selector ]: null,
				},
			};
			break;
		case TYPES.SET_ERROR:
			state = {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: false,
				},
				errors: {
					...state.errors,
					[ selector ]: error,
				},
			};
			break;
	}
	return state;
};

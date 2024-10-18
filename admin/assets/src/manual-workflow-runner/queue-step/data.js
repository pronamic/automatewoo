/**
 * External dependencies
 */
import { useReducer } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STEP_STATUSES as STATUSES } from '../utils';

const calculateProgressPercentage = ( totalCount, remainingCount ) => {
	const completedCount = totalCount - remainingCount;

	if ( totalCount === 0 || completedCount === 0 ) {
		return 0;
	}

	const progress = Math.round( ( completedCount / totalCount ) * 100 );
	return progress > 100 ? 100 : progress;
};

const reducer = ( state, action ) => {
	switch ( action.type ) {
		case 'ADD_ITEMS_REQUEST':
			if ( state.status === STATUSES.REQUESTING ) {
				return state;
			}
			return {
				...state,
				status: STATUSES.REQUESTING,
			};
		case 'ADD_ITEMS_ERROR':
			if ( state.status === STATUSES.ERROR ) {
				return state;
			}
			return {
				...state,
				status: STATUSES.ERROR,
			};
		case 'ADD_ITEMS_SUCCESS':
			const itemsRemainingCount = Object.keys(
				action.itemsRemaining
			).length;
			return {
				...state,
				itemsRemaining: action.itemsRemaining,
				status:
					itemsRemainingCount === 0
						? STATUSES.COMPLETE
						: STATUSES.PENDING,
				progress: calculateProgressPercentage(
					Object.keys( state.itemsToAdd ).length,
					itemsRemainingCount
				),
			};
		default:
			return state;
	}
};

export const useQueueItemsReducer = ( itemsToQueue ) => {
	return useReducer( reducer, {
		status: STATUSES.PENDING,
		progress: 0,
		itemsToAdd: itemsToQueue,
		itemsRemaining: itemsToQueue,
	} );
};

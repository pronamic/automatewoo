/**
 * External dependencies
 */
import { useReducer } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STEP_STATUSES as STATUSES } from '../utils';
import { MANUAL_WORKFLOWS_BATCH_SIZE } from '../../settings';

/**
 * @param {Object} progressData
 * @return {string|null} The index of the current progress group or null.
 */
export const getCurrentProgressGroupNumber = ( progressData ) => {
	for ( const groupNumber in progressData ) {
		const group = progressData[ groupNumber ];
		if ( ! group.complete ) {
			return groupNumber;
		}
	}
	return null;
};

/**
 * Updates progressData by incrementing the current group.
 *
 * @param {Object} progressData
 * @return {Object} Of new progress data.
 */
const incrementProgressData = ( progressData ) => {
	const currentGroupNumber = getCurrentProgressGroupNumber( progressData );

	if ( currentGroupNumber === null ) {
		return progressData;
	}

	const currentGroup = progressData[ currentGroupNumber ];

	// Increment the batch
	currentGroup.offset += MANUAL_WORKFLOWS_BATCH_SIZE;

	// Mark group as complete if required
	if ( currentGroup.offset >= currentGroup.total ) {
		currentGroup.complete = true;
	}

	progressData[ currentGroupNumber ] = currentGroup;
	return progressData;
};

/**
 * Calculate progress percentage.
 *
 * @param {Object} progressData
 * @return {number} The percentage.
 */
const calculateProgressPercentage = ( progressData ) => {
	let total = 0;
	let complete = 0;

	for ( const groupNumber in progressData ) {
		const group = progressData[ groupNumber ];

		total += group.total;
		complete += group.complete ? group.total : group.offset;
	}

	if ( complete === 0 || total === 0 ) {
		return 0;
	}
	const progress = Math.floor( ( complete / total ) * 100 );
	return progress > 100 ? 100 : progress;
};

const initialState = {
	status: STATUSES.PENDING,
	progress: {},
	progressPercent: 0,
	items: {},
};

const reducer = ( state, action ) => {
	switch ( action.type ) {
		case 'FIND_ITEMS_REQUEST':
			if ( state.status === STATUSES.REQUESTING ) {
				return state;
			}
			return {
				...state,
				status: STATUSES.REQUESTING,
			};
		case 'FIND_ITEMS_ERROR':
			if ( state.status === STATUSES.ERROR ) {
				return state;
			}
			return {
				...state,
				status: STATUSES.ERROR,
			};
		case 'FIND_ITEMS_SUCCESS':
			const newProgress = incrementProgressData( state.progress );
			const progressPercent = calculateProgressPercentage( newProgress );

			return {
				...state,
				status:
					progressPercent === 100
						? STATUSES.COMPLETE
						: STATUSES.PENDING,
				// IMPORTANT: Don't add duplicate items
				items: { ...action.items, ...state.items },
				progress: newProgress,
				progressPercent,
			};
		default:
			return state;
	}
};

/**
 * useFindItemsReducer.
 *
 * @param {Array} possibleResultCounts
 * @return {Array} Containing reducer state and dispatch function.
 */
export const useFindItemsReducer = ( possibleResultCounts ) => {
	return useReducer( reducer, initialState, ( state ) => {
		// Set up object for storing progress
		possibleResultCounts.forEach( ( group ) => {
			state.progress[ group.group_number ] = {
				offset: 0,
				total: group.count,
				complete: false,
			};
		} );
		return state;
	} );
};

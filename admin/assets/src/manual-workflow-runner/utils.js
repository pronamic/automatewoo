/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useBeforeUnload } from '../base/hooks';

export const STEP_STATUSES = {
	COMPLETE: 'COMPLETE',
	ERROR: 'ERROR',
	PENDING: 'PENDING',
	REQUESTING: 'REQUESTING',
};

export const useWarnBeforeUnloadWhileRequesting = ( status ) => {
	let message = '';
	if (
		! [ STEP_STATUSES.COMPLETE, STEP_STATUSES.ERROR ].includes( status )
	) {
		message = __(
			'If you leave this page the process will stop.',
			'automatewoo'
		);
	}

	useBeforeUnload( message );
};

export const getTotalPossibleResults = ( possibleResultCountData ) => {
	let count = 0;
	possibleResultCountData.forEach( ( group ) => {
		count += group.count;
	} );
	return count;
};

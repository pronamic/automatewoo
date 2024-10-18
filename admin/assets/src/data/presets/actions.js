/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { setIsRequesting, setError } from '../base/actions';
import { AW_NAMESPACE } from '../constants';

export function updatePresets( presets ) {
	return {
		type: TYPES.UPDATE_PRESETS,
		presets,
	};
}

export function receiveCreatedWorkflow() {
	return { type: TYPES.CREATED_WORKFLOW };
}

/**
 * Create a workflow from a preset.
 *
 * @param {string} presetName
 * @return {number} A valid workflow ID.
 * @throws Error on failure.
 */
export function* createWorkflow( presetName ) {
	const actionName = 'createWorkflow';
	yield setIsRequesting( actionName, true );

	try {
		const results = yield apiFetch( {
			path: `${ AW_NAMESPACE }/presets/create-workflow`,
			method: 'POST',
			data: { preset_name: presetName },
		} );

		// Expect a valid workflow ID as a response
		if ( ! results.workflow_id ) {
			throw new Error();
		}

		yield receiveCreatedWorkflow();
		return results.workflow_id;
	} catch ( error ) {
		yield setError( actionName, error );

		// Re-throw the error.
		throw error;
	}
}

export { setIsRequesting, setError };

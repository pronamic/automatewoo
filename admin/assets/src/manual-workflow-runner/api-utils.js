/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { MANUAL_WORKFLOWS_BATCH_SIZE } from '../settings';

/**
 * @param {number} workflowId
 * @return {Promise<*>} Post request promise.
 * @throws Error
 */
export async function getWorkflowQuickFilterData( workflowId ) {
	const data = await apiFetch( {
		path: `/automatewoo/manual-workflow-runner/quick-filter-data/${ workflowId }`,
	} );

	if ( data ) {
		return data;
	}

	throw new Error();
}

/**
 * @param {number} workflowId
 * @param {string} ruleGroupNumber
 * @param {number} ruleGroupOffset
 * @return {Promise<*>} Post request promise.
 * @throws Error
 */
export async function getWorkflowMatchingItems(
	workflowId,
	ruleGroupNumber,
	ruleGroupOffset
) {
	return await apiFetch( {
		path: `/automatewoo/manual-workflow-runner/find-matches/${ workflowId }`,
		method: 'POST',
		data: {
			offset: ruleGroupOffset,
			batch_size: MANUAL_WORKFLOWS_BATCH_SIZE,
			rule_group: ruleGroupNumber,
		},
	} );
}

/**
 * Add items to queue.
 *
 * @param {number} workflowId
 * @param {Array}  batch
 * @return {Promise<*>} Post request promise.
 * @throws Error
 */
export async function addItemBatchToQueue( workflowId, batch ) {
	return await apiFetch( {
		path: `/automatewoo/manual-workflow-runner/add-items-to-queue/${ workflowId }`,
		method: 'POST',
		data: { batch },
	} );
}

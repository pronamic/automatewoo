/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from '../../upstream/utils/async-requests';

/**
 * Maps workflow'd `id` and `title` to `{key, label}` to create a Search-compatible option.
 *
 * @param {Object} workflow
 * @return { {key: string, lable: string } } Search-compatible option.
 */
const mapWorkflowToOption = ( workflow ) => ( {
	key: workflow.id,
	label: workflow.title,
} );

const workflowsAutocompleter = {
	name: 'workflows',
	options: ( search ) =>
		apiFetch( {
			path:
				'/automatewoo/workflows?' +
				new URLSearchParams( {
					search,
					per_page: 10,
					orderby: 'popularity',
					_fields: 'id,title',
				} ).toString(),
		} ),
	getOptionIdentifier: ( option ) => option.id,
	getOptionLabel: ( option ) => option.title,
	getOptionKeywords: ( option ) => [ option.title ],
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion: mapWorkflowToOption,
};

/**
 * Exports.
 */

export const filtersConfig = [
	{
		label: __( 'Show', 'automatewoo' ),
		staticParams: [ 'section', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{
				label: __( 'All Workflows', 'automatewoo' ),
				value: 'all',
			},
			{
				label: __( 'Single Workflow', 'automatewoo' ),
				value: 'select_workflow',
				subFilters: [
					{
						component: 'Search',
						value: 'single_workflow',
						path: [ 'select_workflow' ],
						settings: {
							type: 'custom',
							param: 'workflows',
							getLabels: getRequestByIdString(
								'/automatewoo/workflows',
								mapWorkflowToOption
							),
							labels: {
								placeholder: __(
									'Type to search for a workflow',
									'automatewoo'
								),
								button: __( 'Single Workflow', 'automatewoo' ),
							},
							autocompleter: workflowsAutocompleter,
						},
					},
				],
			},
		],
	},
];

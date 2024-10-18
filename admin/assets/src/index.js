/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import ManualWorkflowRunner from './manual-workflow-runner';
import './workflow-tab-handler';
import './workflow-tinymce';
import './notice-tracking';
import './index.scss';
import './data';

addFilter( 'woocommerce_admin_pages_list', 'automatewoo', ( pages ) => {
	return [
		...pages,
		{
			breadcrumbs: [
				__( 'AutomateWoo', 'automatewoo' ),
				__( 'Workflows', 'automatewoo' ),
				__( 'Manual Runner', 'automatewoo' ),
			],
			title: __( 'AutomateWoo Manual Workflow Runner', 'automatewoo' ),
			container: ManualWorkflowRunner,
			path: '/automatewoo/manual-workflow-runner',
			wpOpenMenu: 'toplevel_page_automatewoo',
		},
	];
} );

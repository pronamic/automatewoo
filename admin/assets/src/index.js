/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter, didFilter, hasAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import ManualWorkflowRunner from './manual-workflow-runner';
import './workflow-tab-handler';
import './workflow-tinymce';
import './notice-tracking';
import './data';

// Refer to https://github.com/woocommerce/woocommerce/blob/9.8.0/plugins/woocommerce/client/admin/client/layout/controller.js#L82
const PAGES_FILTER = 'woocommerce_admin_pages_list';

let hasAddedPluginAdminPages = false;

function filterAdminPages( pages ) {
	hasAddedPluginAdminPages = true;

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
}

function registerPluginAdminPages() {
	addFilter(
		'woocommerce_admin_pages_list',
		'automatewoo/add-page-routes',
		filterAdminPages
	);
}

const hasRunFilter = () => didFilter( PAGES_FILTER ) > 0;
const hasAddedFallback = () =>
	hasAction( 'hookAdded', `woocommerce/woocommerce/watch_${ PAGES_FILTER }` );

/* compatibility-code "WP >= 6.8 && WC >= 9.8" -- Ensure the registration of the plugin admin pages is applied to the filter
 *
 * Starting with WooCommerce >= 9.8, this script will run after the 'wc-admin-app'
 * script instead of before it. Originally, this was not a problem, because
 * WooCommerce Core has a fallback to handle this run order.
 * Ref: https://github.com/woocommerce/woocommerce/blob/9.8.0/plugins/woocommerce/client/admin/client/layout/controller.js#L358-L382
 *
 * The mechanism works when these hooks run in the following order:
 * 1. WC runs `applyFilters` to get pages
 * 2. WC runs `addAction` to set up a fallback to handle filters being added
 *    after the applied filter above
 * 3. This plugin runs `addFilter` to add its pages and the above fallback will
 *    take care of this lately added filter
 *
 * However, since WordPress >= 6.8, somehow there is a chance that these hooks
 * will run in the following order:
 * 1. WC runs `applyFilters`
 * 2. This plugin runs `addFilter`
 * 3. WC runs `addAction` to set up a fallback
 *
 * In this case, pages registration will be missed and won't be displayed.
 * So here's a compatibility process to ensure this plugin runs `addFilter`
 * after the fallback is set.
 */
if ( hasRunFilter() && ! hasAddedFallback() && ! hasAddedPluginAdminPages ) {
	const startTime = Date.now();
	const timerId = setInterval( () => {
		if ( hasAddedFallback() ) {
			clearInterval( timerId );
			registerPluginAdminPages();
			return;
		}

		// Stop trying after 3 seconds to avoid performance issues.
		if ( Date.now() - startTime > 3000 ) {
			clearInterval( timerId );
		}
	}, 10 );
} else {
	registerPluginAdminPages();
}

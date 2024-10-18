/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConversionsReport from './reports/conversions';
import EmailTrackingReport from './reports/email-tracking';
import RunsByDateReport from './reports/runs-by-date';

/**
 * Use the 'woocommerce_admin_reports_list' filter to add a report page.
 */
addFilter( 'woocommerce_admin_reports_list', 'automatewoo', ( reports ) => {
	return [
		...reports,
		{
			report: 'automatewoo-runs-by-date',
			title: _x( 'Workflows', 'analytics report title', 'automatewoo' ),
			component: RunsByDateReport,
			navArgs: {
				id: 'automatewoo-analytics-runs-by-date',
			},
		},
		{
			report: 'automatewoo-email-tracking',
			title: _x(
				'Email & SMS Tracking',
				'analytics report title',
				'automatewoo'
			),
			component: EmailTrackingReport,
			navArgs: {
				id: 'automatewoo-analytics-email-tracking',
			},
		},
		{
			report: 'automatewoo-conversions',
			title: _x( 'Conversions', 'analytics report title', 'automatewoo' ),
			component: ConversionsReport,
			navArgs: {
				id: 'automatewoo-analytics-conversions',
			},
		},
	];
} );

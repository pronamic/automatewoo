/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ReportFilters } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import { filtersConfig } from './filters-config';
import ComposedReportChart from '../../upstream/woocommerce-admin-analytics/analytics/components/report-chart';
import ComposedReportSummary from '../../upstream/woocommerce-admin-analytics/analytics/components/report-summary';

const charts = [
	{
		key: 'sent',
		href: '',
		label: __( 'Sent', 'automatewoo' ),
		labelTooltipText: __( 'Trackable messages sent', 'automatewoo' ),
		type: 'number',
	},
	{
		key: 'opens',
		href: '',
		label: __( 'Opens', 'automatewoo' ),
		labelTooltipText: __( 'Unique opens', 'automatewoo' ),
		type: 'number',
	},
	{
		key: 'unique-clicks',
		href: '',
		label: __( 'Unique clicks', 'automatewoo' ),
		type: 'number',
	},
	{
		key: 'clicks',
		href: '',
		label: __( 'Clicks', 'automatewoo' ),
		type: 'number',
	},
];
const unsubscribers = [
	{
		key: 'unsubscribers',
		href: '',
		label: __( 'Unsubscribers', 'automatewoo' ),
		type: 'number',
	},
];

/**
 * Mocked email & sms tracking report.
 *
 * @param {Object} props       Props provided by WooCommerce routing.
 * @param {Object} props.query
 * @param {string} props.path
 */
export default function EmailTrackingReport( { query, path } ) {
	// Pick queried chart, fallback to the first one.
	const chart =
		charts.find( ( item ) => item.key === query.chart ) || charts[ 0 ];
	return (
		<>
			<ReportFilters
				query={ query }
				path={ path }
				filters={ filtersConfig }
			/>
			<ComposedReportSummary
				charts={ charts }
				endpoint="email-tracking"
				query={ query }
				selectedChart={ chart }
				filters={ filtersConfig }
			/>
			<ComposedReportChart
				endpoint="email-tracking"
				path={ path }
				query={ query }
				filters={ filtersConfig }
				selectedChart={ chart }
				charts={ charts }
			/>
			<ComposedReportSummary
				charts={ unsubscribers }
				endpoint="unsubscribers"
				query={ query }
				selectedChart={ unsubscribers[ 0 ] }
				filters={ filtersConfig }
			/>
			<ComposedReportChart
				endpoint="unsubscribers"
				path={ path }
				query={ query }
				filters={ filtersConfig }
				selectedChart={ unsubscribers[ 0 ] }
				charts={ unsubscribers }
			/>
		</>
	);
}

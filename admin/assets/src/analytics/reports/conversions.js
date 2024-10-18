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
import ConversionsTable from './conversions-table';

const charts = [
	{
		key: 'total_sales',
		href: '',
		label: __( 'Total value', 'automatewoo' ),
		labelTooltipText: __( 'Converted order value', 'automatewoo' ),
		type: 'currency',
	},
	{
		key: 'net_revenue',
		href: '',
		label: __( 'Net revenue', 'automatewoo' ),
		type: 'currency',
	},
	{
		key: 'orders_count',
		href: '',
		label: __( 'Orders', 'automatewoo' ),
		labelTooltipText: __( 'Converted orders', 'automatewoo' ),
		type: 'number',
	},
];

/**
 * Mocked Conversions report.
 *
 * @param {Object} props       Props provided by WooCommerce routing.
 * @param {Object} props.query
 * @param {string} props.path
 */
export default function ConversionsReport( { query, path } ) {
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
				endpoint="conversions"
				query={ query }
				selectedChart={ chart }
				filters={ filtersConfig }
			/>
			<ComposedReportChart
				endpoint="conversions"
				path={ path }
				query={ query }
				filters={ filtersConfig }
				selectedChart={ chart }
				charts={ charts }
			/>
			<ConversionsTable query={ query } filters={ filtersConfig } />
		</>
	);
}

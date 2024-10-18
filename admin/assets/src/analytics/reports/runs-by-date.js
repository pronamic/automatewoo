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
		key: 'runs',
		label: __( 'Runs', 'automatewoo' ),
		labelTooltipText: __(
			'Workflows have run for the selected period',
			'automatewoo'
		),
		type: 'number',
	},
];

/**
 * Mocked reports by date report.
 *
 * @param {Object} props       Props provided by WooCommerce routing.
 * @param {Object} props.query
 * @param {string} props.path
 */
export default function RunsByDateReport( { query, path } ) {
	return (
		<>
			<ReportFilters
				query={ query }
				path={ path }
				filters={ filtersConfig }
			/>
			<ComposedReportSummary
				charts={ charts }
				endpoint="workflow-runs"
				query={ query }
				selectedChart={ charts[ 0 ] }
				filters={ filtersConfig }
			/>
			<ComposedReportChart
				endpoint="workflow-runs"
				path={ path }
				query={ query }
				filters={ filtersConfig }
				selectedChart={ charts[ 0 ] }
				charts={ charts }
			/>
		</>
	);
}

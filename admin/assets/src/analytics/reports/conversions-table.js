/**
 * External dependencies
 */
import { Date, Link } from '@woocommerce/components';
import { useCallback, useState } from '@wordpress/element';
import { REPORTS_STORE_NAME } from '@woocommerce/data';
import { defaultTableDateFormat } from '@woocommerce/date';
import { formatValue } from '@woocommerce/number';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';
import { Button, Tooltip } from '@wordpress/components';
import { dispatch, useDispatch } from '@wordpress/data';
import { __, _n } from '@wordpress/i18n';
import Modal from '#external/automatewoo/modal.js';

/**
 * Internal dependencies
 */
import ComposedReportTable from '../../upstream/woocommerce-admin-analytics/analytics/components/report-table';
import { getFilteredCurrencyInstance } from '../../upstream/woocommerce-admin-analytics/lib/currency-context';
import './conversions-table.scss';

const requestUnmark = async function ( orderIds ) {
	const { createNotice } = dispatch( 'core/notices' );
	try {
		await apiFetch( {
			path: `/automatewoo/conversions/batch/`,
			method: 'DELETE',
			body: JSON.stringify( {
				ids: orderIds,
			} ),
		} );
	} catch ( error ) {
		createNotice(
			'error',
			__( 'There was an error unmarking conversions.', 'automatewoo' )
		);
		return;
	}
	createNotice(
		'success',
		__( `Orders successfully unmarked as conversions.`, 'automatewoo' )
	);
};

/**
 * Conversions List table.
 *
 * @param {Object} props         Props provided by WooCommerce routing.
 * @param {Object} props.query
 * @param {Object} props.filters Report filters config.
 */
export default function ConversionsTable( { query, filters } ) {
	const { invalidateResolutionForStoreSelector } =
		useDispatch( REPORTS_STORE_NAME );
	const dateFormat =
		getSetting( 'admin', {} ).dateFormat || defaultTableDateFormat;
	const { formatAmount, getCurrencyConfig } =
		getFilteredCurrencyInstance( query );

	const [ unmarking, setUnmarking ] = useState( false );
	const bulkUnmark = useCallback(
		async ( orderIds ) => {
			if ( ! unmarking ) {
				setUnmarking( true );
				await requestUnmark( orderIds );
				setUnmarking( false );
				invalidateResolutionForStoreSelector( 'getReportStats' );
				invalidateResolutionForStoreSelector( 'getReportItems' );
			}
		},
		[ unmarking, invalidateResolutionForStoreSelector ]
	);

	function getHeadersContent() {
		return [
			{
				key: 'order',
				label: __( 'Order #', 'automatewoo' ),
				screenReaderLabel: __( 'Order Number', 'automatewoo' ),
				required: true,
			},
			{
				key: 'customer',
				label: __( 'Customer', 'automatewoo' ),
				isLeftAligned: true,
			},
			{
				key: 'Workflow',
				label: __( 'Workflow', 'automatewoo' ),
				isLeftAligned: true,
			},
			{
				key: 'log',
				label: __( 'Log', 'automatewoo' ),
				isLeftAligned: true,
			},
			{
				key: 'interacted',
				label: __( 'First Interacted', 'automatewoo' ),
			},
			{
				key: 'placed',
				label: __( 'Order Placed', 'automatewoo' ),
			},
			{
				key: 'total',
				label: __( 'Order Total', 'automatewoo' ),
				isCurrency: true,
				isNumeric: true,
			},
		];
	}

	function getRowsContent( conversionsStats ) {
		return conversionsStats.map( ( conversionStat ) => {
			const {
				order_id: orderId,
				order_number: orderNumber,
				workflow_id: workflowId,
				conversion_id: logId,
				date_created: orderPlaced,
				total_sales: orderTotal,
				extended_info: {
					conversion: { date_opened: firstInteracted },
					customer: {
						user_id: userId,
						first_name: customerFirstName,
						last_name: customerLastName,
					},
					workflow: { name: workflowName },
				},
			} = conversionStat;

			const customerFullName = `${ customerFirstName } ${ customerLastName }`;

			return [
				{
					display: (
						<Link
							href={ `post.php?post=${ orderId }&action=edit` }
							type="wp-admin"
						>
							<strong>{ orderNumber }</strong>
						</Link>
					),
					value: orderId,
				},
				{
					display:
						userId === null ? (
							customerFullName
						) : (
							<Link
								href={ `user-edit.php?user_id=${ userId }&action=edit` }
								type="wp-admin"
							>
								{ customerFullName }
							</Link>
						),
					value: `${ customerFullName } (${
						userId === null ? 'guest' : userId
					})`,
				},
				{
					display: (
						<Link
							href={ `post.php?post=${ workflowId }&action=edit` }
							type="wp-admin"
						>
							<strong>{ workflowName }</strong>
						</Link>
					),
					value: workflowId,
				},
				{
					display: (
						<a
							className={ Modal.triggerClasses.openLink }
							href={ `admin-ajax.php?action=aw_modal_log_info&log_id=${ logId }` }
						>
							{ logId }
						</a>
					),
					value: logId,
				},
				{
					display: (
						<Date
							date={ firstInteracted }
							visibleFormat={ dateFormat }
						/>
					),
					value: firstInteracted,
				},
				{
					display: (
						<Date
							date={ orderPlaced }
							visibleFormat={ dateFormat }
						/>
					),
					value: orderPlaced,
				},
				{
					display: formatAmount( orderTotal ),
					value: orderTotal,
				},
			];
		} );
	}

	function getSummary( totals, totalResults = 0 ) {
		const { total_sales: totalSales = 0, net_revenue: netRevenue = 0 } =
			totals;
		const currency = getCurrencyConfig();
		return [
			{
				label: _n(
					'Conversion',
					'Conversions',
					totalResults,
					'automatewoo'
				),
				value: formatValue( currency, 'number', totalResults ),
			},
			{
				label: __( 'Total sales', 'automatewoo' ),
				value: formatAmount( totalSales ),
			},
			{
				label: __( 'Net sales', 'automatewoo' ),
				value: formatAmount( netRevenue ),
			},
		];
	}

	return (
		<ComposedReportTable
			className="aw-conversions-table"
			endpoint="conversions"
			getHeadersContent={ getHeadersContent }
			getRowsContent={ getRowsContent }
			getSummary={ getSummary }
			summaryFields={ [ 'total_sales', 'net_revenue', 'orders_count' ] }
			itemIdField="order_id"
			query={ query }
			tableQuery={ {
				orderby: query.orderby || 'date',
				order: query.order || 'desc',
				extended_info: true,
			} }
			title={ __( 'Conversions list', 'automatewoo' ) }
			columnPrefsKey="conversions_report_columns"
			filters={ filters }
			checkboxes={ true }
			renderActionButton={ ( { selectedRows } ) => (
				<Tooltip
					text={ __(
						'Unmark selected orders as conversions',
						'automatewoo'
					) }
				>
					<Button
						disabled={ unmarking || selectedRows.length === 0 }
						variant="secondary"
						onClick={ () => bulkUnmark( selectedRows ) }
					>
						{ __( 'Unmark', 'automatewoo' ) }
					</Button>
				</Tooltip>
			) }
		/>
	);
}

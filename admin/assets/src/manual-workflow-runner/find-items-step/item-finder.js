/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProgressBar } from '../../base/components';
import { getWorkflowMatchingItems } from '../api-utils';
import { getCurrentProgressGroupNumber } from './data';
import {
	STEP_STATUSES as STATUSES,
	useWarnBeforeUnloadWhileRequesting,
} from '../utils';
import ItemsTable from './items-table';
import NextButton from '../next-button';
import NoResults from './no-results';
import { handleFetchError } from '../../base/utils';

const ItemFinder = ( {
	onComplete,
	onCancel,
	workflowQuickFilterData,
	workflow,
	state,
	dispatch,
} ) => {
	const { primaryDataTypePluralName } = workflowQuickFilterData;
	const itemCount = Object.keys( state.items ).length;
	useWarnBeforeUnloadWhileRequesting( state.status );

	useEffect( () => {
		// Only do another request when status is pending
		if ( state.status !== STATUSES.PENDING ) {
			return;
		}

		const fetchItemsBatch = async () => {
			const groupNumber = getCurrentProgressGroupNumber( state.progress );
			if ( groupNumber === null ) {
				return;
			}
			const progressGroup = state.progress[ groupNumber ];
			dispatch( { type: 'FIND_ITEMS_REQUEST' } );

			try {
				const itemsArray = await getWorkflowMatchingItems(
					workflow.id,
					groupNumber,
					progressGroup.offset
				);

				// Convert items array to object with id as key
				const items = {};
				itemsArray.forEach( ( item ) => {
					items[ item.id ] = item;
				} );

				dispatch( { type: 'FIND_ITEMS_SUCCESS', items } );
			} catch ( error ) {
				dispatch( { type: 'FIND_ITEMS_ERROR' } );
				handleFetchError( 'Error finding items.', error );
			}
		};
		fetchItemsBatch();
	}, [ dispatch, workflow.id, state.status, state.progress ] );

	const nextButtonText = sprintf(
		// translators: %(itemCount)d: number of items, %(dataType)s: type of item e.g. 'orders'
		__( 'Run workflow for %(itemCount)d %(dataType)s', 'automatewoo' ),
		{
			dataType: primaryDataTypePluralName,
			itemCount,
		}
	);

	// If no results after completion display message.
	if ( state.status === 'COMPLETE' && itemCount === 0 ) {
		return (
			<NoResults
				dataType={ primaryDataTypePluralName }
				workflowId={ workflow.id }
			/>
		);
	}

	return (
		<>
			<p>
				{ sprintf(
					// translators: %1$s: type of item e.g. 'orders', %2$s: the workflow title
					__(
						'Searching for %1$s that match the rules used in the "%2$s" workflow. ' +
							'If you leave this page the process will stop.',
						'automatewoo'
					),
					primaryDataTypePluralName,
					workflow.title
				) }
			</p>
			<ProgressBar progress={ state.progressPercent } />
			<ItemsTable items={ state.items } />
			<div className="automatewoo-workflow-runner-buttons">
				<Button isSecondary onClick={ onCancel }>
					{ __( 'Cancel', 'automatewoo' ) }
				</Button>
				{ state.status === STATUSES.COMPLETE && (
					<NextButton onClick={ () => onComplete( state.items ) }>
						{ nextButtonText }
					</NextButton>
				) }
			</div>
		</>
	);
};

ItemFinder.propTypes = {
	state: PropTypes.object.isRequired,
	dispatch: PropTypes.func.isRequired,
	onComplete: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
	workflow: PropTypes.shape( {
		id: PropTypes.number.isRequired,
		title: PropTypes.string.isRequired,
	} ).isRequired,
	workflowQuickFilterData: PropTypes.shape( {
		possibleResultCounts: PropTypes.array.isRequired,
		primaryDataTypePluralName: PropTypes.string.isRequired,
	} ).isRequired,
};

export default ItemFinder;

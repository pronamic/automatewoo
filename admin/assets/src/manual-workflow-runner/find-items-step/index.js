/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { MANUAL_WORKFLOWS_HIGH_VOLUME_THRESHOLD } from '../../settings';
import HighVolumeWarning from './high-volume-warning';
import ItemFinder from './item-finder';
import { useFindItemsReducer } from './data';
import NoResults from './no-results';
import './index.scss';

const FindItemsStep = ( {
	workflow,
	onStepCancel,
	onStepComplete,
	workflowQuickFilterData,
	possibleResultsCount,
} ) => {
	const { primaryDataTypePluralName, possibleResultCounts } =
		workflowQuickFilterData;
	const [ dismissedHighVolumeWarning, setDismissedHighVolumeWarning ] =
		useState( false );
	const [ findItemsState, findItemsDispatch ] =
		useFindItemsReducer( possibleResultCounts );

	// Logic for high volume warning
	let showHighVolumeWarning = false;
	if (
		! dismissedHighVolumeWarning &&
		possibleResultsCount > MANUAL_WORKFLOWS_HIGH_VOLUME_THRESHOLD
	) {
		showHighVolumeWarning = true;
	}

	let cardBody;
	if ( possibleResultsCount === 0 ) {
		cardBody = (
			<NoResults
				dataType={ primaryDataTypePluralName }
				workflowId={ workflow.id }
			/>
		);
	} else if ( showHighVolumeWarning ) {
		cardBody = (
			<HighVolumeWarning
				dismissWarning={ () => setDismissedHighVolumeWarning( true ) }
				possibleResultsCount={ possibleResultsCount }
				primaryDataTypePluralName={ primaryDataTypePluralName }
				workflowId={ workflow.id }
			/>
		);
	} else {
		cardBody = (
			<ItemFinder
				state={ findItemsState }
				dispatch={ findItemsDispatch }
				onComplete={ onStepComplete }
				onCancel={ onStepCancel }
				workflow={ workflow }
				workflowQuickFilterData={ workflowQuickFilterData }
			/>
		);
	}

	return (
		<Card
			title={ sprintf(
				// translators: %s: type of item e.g. 'orders'
				__( '2. Find matching %s', 'automatewoo' ),
				primaryDataTypePluralName
			) }
		>
			<CardBody>{ cardBody }</CardBody>
		</Card>
	);
};

FindItemsStep.propTypes = {
	workflow: PropTypes.shape( {
		id: PropTypes.number.isRequired,
		title: PropTypes.string.isRequired,
	} ).isRequired,
	workflowQuickFilterData: PropTypes.shape( {
		possibleResultCounts: PropTypes.array.isRequired,
		primaryDataTypePluralName: PropTypes.string.isRequired,
	} ).isRequired,
	onStepComplete: PropTypes.func.isRequired,
	onStepCancel: PropTypes.func.isRequired,
	possibleResultsCount: PropTypes.number.isRequired,
};

export default FindItemsStep;

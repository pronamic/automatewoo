/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import SelectWorkflowStep from './select-workflow-step';
import FindItemsStep from './find-items-step';
import QueueStep from './queue-step';
import { getWorkflowQuickFilterData } from './api-utils';
import { getWorkflow, handleFetchError } from '../base/utils';
import { getTotalPossibleResults } from './utils';
import './index.scss';
import { TRACKS_PREFIX } from '../settings';

/**
 * @param {string} primaryDataType
 * @return {string} The plural name of the data type.
 */
const getDataTypePluralName = ( primaryDataType ) => {
	const names = {
		order: __( 'orders', 'automatewoo' ),
		subscription: __( 'subscriptions', 'automatewoo' ),
	};
	return names.hasOwnProperty( primaryDataType )
		? names[ primaryDataType ]
		: __( 'items', 'automatewoo' );
};

const ManualWorkflowRunner = ( { query } ) => {
	const [ workflow, setWorkflow ] = useState( {} );
	const [ quickFilterData, setQuickFilterData ] = useState( false );
	const [ foundItems, setFoundItems ] = useState( {} );
	const [ currentStep, setCurrentStep ] = useState( 'select' );
	const [ stepperIsPending, setStepperIsPending ] = useState( false );
	const [ isPreFillingWorkflow, setIsPreFillingWorkflow ] = useState( true );

	/**
	 * Handle select workflow step completion.
	 *
	 * Load extra data about the selected workflow.
	 */
	const onSelectStepComplete = async () => {
		setStepperIsPending( true );
		recordEvent( TRACKS_PREFIX + 'manual_workflow_runner_select_workflow', {
			conversion_tracking_enabled:
				workflow.is_conversion_tracking_enabled,
			tracking_enabled: workflow.is_tracking_enabled,
			title: workflow.title,
			type: workflow.type,
			trigger_name: workflow.trigger.name,
		} );

		try {
			const { possibleResultCounts, primaryDataType } =
				await getWorkflowQuickFilterData( workflow.id );

			setQuickFilterData( {
				possibleResultCounts,
				primaryDataType,
				primaryDataTypePluralName:
					getDataTypePluralName( primaryDataType ),
			} );
			setCurrentStep( 'find' );
			setStepperIsPending( false );
		} catch ( error ) {
			handleFetchError(
				__( 'Error loading the workflow data.', 'automatewoo' ),
				error
			);
		}
	};

	/**
	 * Pre-fill the selected workflow based on the query.
	 */
	useEffect( () => {
		const maybePreFillWorkflow = async () => {
			setIsPreFillingWorkflow( true );

			const workflowId =
				typeof query.workflowId !== 'undefined'
					? parseInt( query.workflowId, 10 )
					: 0;

			if ( workflowId !== 0 ) {
				try {
					const newWorkflow = await getWorkflow( workflowId );
					if ( ! newWorkflow.title ) {
						newWorkflow.title = __( '(no title)', 'automatewoo' );
					}
					setWorkflow( newWorkflow );
				} catch ( error ) {
					handleFetchError(
						__(
							"The workflow couldn't be loaded from the URL.",
							'automatewoo'
						),
						error
					);
				}
			}

			setIsPreFillingWorkflow( false );
		};

		maybePreFillWorkflow();
	}, [ query.workflowId ] );

	const onFindStepComplete = ( newFoundItems ) => {
		recordEvent( TRACKS_PREFIX + 'manual_run_workflow_button_clicked', {
			items_count: Object.keys( newFoundItems ).length,
		} );
		setCurrentStep( 'queue' );
		setFoundItems( newFoundItems );
	};

	const onFindStepCancel = () => {
		recordEvent(
			TRACKS_PREFIX + 'manual_find_matching_cancel_button_clicked',
			{}
		);
		setCurrentStep( 'select' );
	};

	const onQueueStepCancel = () => {
		recordEvent(
			TRACKS_PREFIX + 'manual_queue_items_cancel_button_clicked',
			{}
		);
		setCurrentStep( 'select' );
		setFoundItems( {} );
	};

	const findItemsStepContent = () => {
		if ( currentStep === 'find' && workflow && quickFilterData ) {
			const possibleResults = getTotalPossibleResults(
				quickFilterData.possibleResultCounts
			);
			return (
				<FindItemsStep
					workflow={ workflow }
					workflowQuickFilterData={ quickFilterData }
					possibleResultsCount={ possibleResults }
					onStepComplete={ onFindStepComplete }
					onStepCancel={ onFindStepCancel }
				/>
			);
		}
		return '';
	};

	const steps = [
		{
			key: 'select',
			label: __( 'Select', 'automatewoo' ),
			content: (
				<SelectWorkflowStep
					onStepComplete={ onSelectStepComplete }
					isPreFillingWorkflow={ isPreFillingWorkflow }
					workflow={ workflow }
					setWorkflow={ setWorkflow }
				/>
			),
		},
		{
			key: 'find',
			label: __( 'Find', 'automatewoo' ),
			content: findItemsStepContent(),
		},
		{
			key: 'queue',
			label: __( 'Queue', 'automatewoo' ),
			content: workflow && quickFilterData && (
				<QueueStep
					workflow={ workflow }
					workflowQuickFilterData={ quickFilterData }
					items={ foundItems }
					onStepCancel={ onQueueStepCancel }
				/>
			),
		},
	];

	return (
		<Stepper
			steps={ steps }
			currentStep={ currentStep }
			className="automatewoo-manual-workflow-runner-stepper"
			isPending={ stepperIsPending }
		/>
	);
};

export default ManualWorkflowRunner;

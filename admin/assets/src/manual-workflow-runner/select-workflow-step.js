/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PropTypes } from 'prop-types';
import { Card, CardBody, Spinner } from '@wordpress/components';
import warning from '@wordpress/warning';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import { WorkflowSearch } from '../base/components';
import NextButton from './next-button';

const SelectWorkflowStep = ( {
	onStepComplete,
	isPreFillingWorkflow,
	workflow,
	setWorkflow,
} ) => {
	const selected = [];

	if ( ! isEmpty( workflow ) ) {
		selected.push( {
			key: workflow.id.toString(),
			label: workflow.title,
			value: workflow,
		} );
	}

	/**
	 * Completes this step and sends the selected workflow up.
	 */
	const goToNextStep = () => {
		if ( workflow.length === 0 ) {
			warning( 'A workflow must be selected' );
			return;
		}
		onStepComplete();
	};

	const content = (
		<div className="automatewoo-workflow-runner-select-workflow-step">
			<WorkflowSearch
				label={ __( 'Workflow', 'automatewoo' ) }
				placeholder={ __( 'Search by workflow title…', 'automatewoo' ) }
				selected={ selected }
				onChange={ ( newSelected ) => {
					setWorkflow(
						newSelected.length ? newSelected[ 0 ].value : {}
					);
				} }
				requestParams={ { type: 'manual' } }
			/>
			<div className="automatewoo-workflow-runner-buttons">
				<NextButton
					onClick={ goToNextStep }
					disabled={ selected.length === 0 }
				>
					{ __( 'Find matching items', 'automatewoo' ) }
				</NextButton>
			</div>
		</div>
	);

	return (
		<Card title={ __( '1. Select a manual workflow', 'automatewoo' ) }>
			<CardBody>
				{ isPreFillingWorkflow ? (
					<div className="automatewoo-manual-workflow-runner-spinner-container">
						<Spinner />
					</div>
				) : (
					content
				) }
			</CardBody>
		</Card>
	);
};

SelectWorkflowStep.propTypes = {
	onStepComplete: PropTypes.func.isRequired,
	isPreFillingWorkflow: PropTypes.bool.isRequired,
	workflow: PropTypes.object.isRequired,
	setWorkflow: PropTypes.func.isRequired,
};

export default SelectWorkflowStep;

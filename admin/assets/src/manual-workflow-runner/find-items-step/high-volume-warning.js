/**
 * External dependencies
 */
import { Dashicon, Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import NextButton from '../next-button';
import { getWorkflowEditUrl } from '../../base/utils';
import LargeTextAndIcon from '../large-text-and-icon';

const HighVolumeWarning = ( {
	dismissWarning,
	possibleResultsCount,
	primaryDataTypePluralName,
	workflowId,
} ) => {
	const workflowUrl = getWorkflowEditUrl( workflowId );

	const warningText = sprintf(
		// translators: %(count)d: number of items, %(dataType)s: type of item e.g. 'orders'
		__(
			'This workflow could potentially match %(count)d %(dataType)s which will take ' +
				'some time to process. Try adding more rules to the workflow ' +
				'so there are fewer matches.',
			'automatewoo'
		),
		{
			count: possibleResultsCount,
			dataType: primaryDataTypePluralName,
		}
	);

	return (
		<>
			<LargeTextAndIcon
				text={
					<p>
						<strong>{ __( 'Warning!', 'automatewoo' ) } </strong>
						{ warningText }
					</p>
				}
				icon={ <Dashicon icon="warning" size="60" /> }
			/>
			<div className="automatewoo-workflow-runner-buttons">
				<Button isSecondary href={ workflowUrl }>
					{ __( 'Edit workflow', 'automatewoo' ) }
				</Button>
				<NextButton onClick={ dismissWarning }>
					{ __( 'Continue anyway', 'automatewoo' ) }
				</NextButton>
			</div>
		</>
	);
};

HighVolumeWarning.propTypes = {
	dismissWarning: PropTypes.func.isRequired,
	possibleResultsCount: PropTypes.number.isRequired,
	primaryDataTypePluralName: PropTypes.string.isRequired,
	workflowId: PropTypes.number.isRequired,
};

export default HighVolumeWarning;

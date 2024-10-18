/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getWorkflowEditUrl } from '../../base/utils';

const NoResults = ( { dataType, workflowId } ) => {
	return (
		<div className="automatewoo-workflow-runner-no-results">
			{ sprintf(
				// translators: %s: The type of data e.g. 'orders'
				__(
					'There are no matching %s for the selected manual workflow.',
					'automatewoo'
				),
				dataType
			) }
			<div className="automatewoo-workflow-runner-buttons">
				<Button isSecondary href={ getWorkflowEditUrl( workflowId ) }>
					{ __( 'Edit workflow', 'automatewoo' ) }
				</Button>
			</div>
		</div>
	);
};

NoResults.propTypes = {
	dataType: PropTypes.string.isRequired,
	workflowId: PropTypes.number.isRequired,
};

export default NoResults;

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordPresetListButtonClickTracksEvent } from './utils';
import ListItem from './list-item';

const ListItemPreset = ( {
	name,
	title,
	description,
	createWorkflow,
	createWorkflowIsRequesting,
} ) => {
	const handleButtonClick = () => {
		createWorkflow( name );
		recordPresetListButtonClickTracksEvent( 'create_workflow', name );
	};
	const button = (
		<Button
			isPrimary
			onClick={ handleButtonClick }
			disabled={ createWorkflowIsRequesting }
		>
			{ __( 'Create workflow', 'automatewoo' ) }
		</Button>
	);

	return (
		<ListItem
			description={ description }
			title={ title }
			button={ button }
		/>
	);
};

ListItemPreset.propTypes = {
	name: PropTypes.string.isRequired,
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	createWorkflow: PropTypes.func.isRequired,
	createWorkflowIsRequesting: PropTypes.bool.isRequired,
};

export default ListItemPreset;

/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './index.scss';
import CardRow from '../base/components/card-row';
import ListItemGuide from './list-item-guide';
import ListItemPreset from './list-item-preset';

const PresetsList = ( {
	presets,
	createWorkflow,
	createWorkflowIsRequesting,
} ) => {
	return (
		<>
			{ presets.map( ( preset, i ) => (
				<CardRow key={ i } number={ i }>
					{ preset.type === 'guide' ? (
						<ListItemGuide { ...preset } />
					) : (
						<ListItemPreset
							{ ...preset }
							createWorkflow={ createWorkflow }
							createWorkflowIsRequesting={
								createWorkflowIsRequesting
							}
						/>
					) }
				</CardRow>
			) ) }
		</>
	);
};

PresetsList.propTypes = {
	presets: PropTypes.array.isRequired,
	createWorkflow: PropTypes.func.isRequired,
	createWorkflowIsRequesting: PropTypes.bool.isRequired,
};

export default PresetsList;

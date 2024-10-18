/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { PRESETS_STORE_NAME } from '../data';
import './index.scss';
import PresetsList from './list';
import PresetsListPlaceholder from './list-placeholder';
import ListLoadError from './list-load-error';
import { getWorkflowEditUrl } from '../base/utils';

const PresetsTab = () => {
	const {
		presets,
		getPresetsError,
		getPresetsIsRequesting,
		createWorkflowIsRequesting,
		didCreateWorkflow,
	} = useSelect( ( select ) => {
		const presetsSelect = select( PRESETS_STORE_NAME );
		return {
			getPresetsError: presetsSelect.getError( 'getPresets' ),
			getPresetsIsRequesting: presetsSelect.isRequesting( 'getPresets' ),
			presets: presetsSelect.getPresets(),
			createWorkflowIsRequesting:
				presetsSelect.isRequesting( 'createWorkflow' ),
			didCreateWorkflow: presetsSelect.didCreateWorkflow(),
		};
	}, [] );
	const { createWorkflow: createWorkflowAction } =
		useDispatch( PRESETS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	let cardBody;

	const createWorkflow = async ( presetName ) => {
		try {
			const workflowId = await createWorkflowAction( presetName );

			// Redirect to workflow edit screen after workflow is created
			// Append origin parameter too
			window.location.href = getWorkflowEditUrl( workflowId, 'preset' );
		} catch ( error ) {
			createNotice(
				'error',
				__( "Error: The workflow couldn't be created.", 'automatewoo' )
			);
		}
	};

	if ( getPresetsError ) {
		const message = getPresetsError.message
			? sprintf(
					// translators: %s: The error message.
					__( 'Error: %s', 'automatewoo' ),
					getPresetsError.message
			  )
			: null;

		cardBody = <ListLoadError message={ message } />;
	} else if ( getPresetsIsRequesting ) {
		cardBody = <PresetsListPlaceholder />;
	} else {
		cardBody = (
			<PresetsList
				presets={ presets }
				createWorkflow={ createWorkflow }
				createWorkflowIsRequesting={
					// Use didCreateWorkflow to keep buttons disabled while redirect is happening
					createWorkflowIsRequesting || didCreateWorkflow
				}
			/>
		);
	}

	return (
		<Card size="medium" className="automatewoo-presets-list-card">
			{ cardBody }
		</Card>
	);
};

export default PresetsTab;

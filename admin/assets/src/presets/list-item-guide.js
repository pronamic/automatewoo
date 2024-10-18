/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ListItem from './list-item';
import { recordPresetListButtonClickTracksEvent } from './utils';

const ListItemGuide = ( { name, title, description, link } ) => {
	const handleButtonClick = () => {
		recordPresetListButtonClickTracksEvent( 'view_guide', name );
	};

	const button = (
		<Button
			href={ link }
			isSecondary
			target="_blank"
			onClick={ handleButtonClick }
		>
			{ __( 'Learn more', 'automatewoo' ) }
		</Button>
	);

	return (
		<ListItem
			title={ title }
			description={ description }
			button={ button }
		/>
	);
};

ListItemGuide.propTypes = {
	name: PropTypes.string.isRequired,
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	link: PropTypes.string.isRequired,
};

export default ListItemGuide;

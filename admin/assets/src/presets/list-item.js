/**
 * External dependencies
 */
import PropTypes from 'prop-types';

const ListItem = ( { title, description, button } ) => {
	return (
		<div className="automatewoo-presets-list-item">
			<div className="automatewoo-presets-list-item__left">
				<h4 className="automatewoo-presets-list-item__title">
					{ title }
				</h4>
				<p className="automatewoo-presets-list-item__description">
					{ description }
				</p>
			</div>
			<div className="automatewoo-presets-list-item__actions">
				{ button }
			</div>
		</div>
	);
};

ListItem.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	button: PropTypes.element.isRequired,
};

export default ListItem;

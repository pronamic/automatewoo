/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const LargeTextAndIcon = ( { text, icon, className = '' } ) => {
	return (
		<div
			className={ classnames(
				'automatewoo-manual-runner-large-text-and-icon',
				className
			) }
		>
			{ icon }
			{ text }
		</div>
	);
};

LargeTextAndIcon.propTypes = {
	text: PropTypes.element.isRequired,
	icon: PropTypes.element.isRequired,
};

export default LargeTextAndIcon;

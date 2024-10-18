/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './index.scss';

const ProgressBar = ( { progress } ) => {
	const progressString = `${ Math.min( progress, 100 ) }%`;

	return (
		<div className="automatewoo-progress-bar-component">
			<div
				className="automatewoo-progress-bar-component__fill"
				style={ { width: progressString } }
			>
				<span className="screen-reader-text">
					{ sprintf(
						// translators: %s: the progress percentage
						__( 'Progress: %s', 'automatewoo' ),
						progressString
					) }
				</span>
			</div>
		</div>
	);
};

ProgressBar.propTypes = {
	progress: PropTypes.number.isRequired,
};

export default ProgressBar;

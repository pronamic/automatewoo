/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const Placeholder = ( { className } ) => {
	return (
		<div
			className={ classnames(
				'automatewoo-placeholder-component',
				className
			) }
		/>
	);
};

export default Placeholder;

/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './index.scss';

const PageTabs = ( { onSelect, tabs, initialTabName, children } ) => {
	return (
		<div className="automatewoo-page-tabs-component">
			<TabPanel
				initialTabName={ initialTabName }
				onSelect={ onSelect }
				tabs={ tabs }
			>
				{ children }
			</TabPanel>
		</div>
	);
};

PageTabs.propTypes = {
	children: PropTypes.func.isRequired,
	onSelect: PropTypes.func.isRequired,
	tabs: PropTypes.array.isRequired,
	initialTabName: PropTypes.string.isRequired,
};

export default PageTabs;

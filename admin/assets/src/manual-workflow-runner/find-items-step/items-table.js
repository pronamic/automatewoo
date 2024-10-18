/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';

const ItemsTable = ( { items } ) => {
	const itemKeys = Object.keys( items );
	const summary = sprintf(
		// translators: %d: the number of items
		__( 'Total: %d', 'automatewoo' ),
		itemKeys.length
	);

	return (
		<div className="automatewoo-manual-runner-found-items">
			<div className="automatewoo-manual-runner-found-items__results">
				{ itemKeys.map( ( itemId ) => {
					const { id, url, singularName } = items[ itemId ];
					return (
						<div
							key={ id }
							className="automatewoo-manual-runner-found-items__result"
						>
							<a href={ url }>{ `${ singularName } #${ id }` }</a>
						</div>
					);
				} ) }
			</div>
			<div className="automatewoo-manual-runner-found-items__summary">
				{ summary }
			</div>
		</div>
	);
};

ItemsTable.propTypes = {
	items: PropTypes.objectOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			singularName: PropTypes.string.isRequired,
			url: PropTypes.string.isRequired,
		} )
	).isRequired,
};

export default ItemsTable;

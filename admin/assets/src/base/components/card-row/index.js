/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { CardBody, CardDivider } from '@wordpress/components';

const CardRow = ( { children, number } ) => {
	return (
		<>
			{ number === 0 ? '' : <CardDivider /> }
			<CardBody>{ children }</CardBody>
		</>
	);
};

CardRow.propTypes = {
	/**
	 * The content for the row.
	 */
	children: PropTypes.element.isRequired,
	/**
	 * The number of the row.
	 */
	number: PropTypes.number.isRequired,
};

export default CardRow;

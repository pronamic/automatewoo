/**
 * Internal dependencies
 */
import './index.scss';
import ListItemPlaceholder from './list-item-placeholder';
import CardRow from '../base/components/card-row';

const PresetsListPlaceholder = () => {
	return [ ...Array( 5 ).keys() ].map( ( i ) => (
		<CardRow key={ i } number={ i }>
			<ListItemPlaceholder />
		</CardRow>
	) );
};

export default PresetsListPlaceholder;

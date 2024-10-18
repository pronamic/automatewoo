/**
 * Internal dependencies
 */
import { Placeholder } from '../base/components';

const ListItemPlaceholder = () => {
	return (
		<div
			className="automatewoo-presets-list-item automatewoo-presets-list-item--placeholder"
			aria-hidden
		>
			<div className="automatewoo-presets-list-item__left">
				<Placeholder className="automatewoo-presets-list-item__title" />
				<Placeholder className="automatewoo-presets-list-item__description" />
			</div>
			<div className="automatewoo-presets-list-item__actions">
				<Placeholder className="automatewoo-presets-list-item__button" />
			</div>
		</div>
	);
};

export default ListItemPlaceholder;

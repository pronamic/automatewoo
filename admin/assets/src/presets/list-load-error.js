/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { EmptyContent } from '@woocommerce/components';
import { CardBody } from '@wordpress/components';

const ListLoadError = ( { message = null } ) => {
	return (
		<CardBody>
			<EmptyContent
				title={ __(
					'There was an error loading presets. Please try again.',
					'automatewoo'
				) }
				message={ message }
				actionLabel={ __( 'Reload', 'automatewoo' ) }
				actionURL={ null }
				actionCallback={ () => window.location.reload() }
			/>
		</CardBody>
	);
};

export default ListLoadError;

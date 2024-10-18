/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export default function NextButton( props ) {
	return (
		<Button
			{ ...props }
			isPrimary
			className="automatewoo-workflow-runner-next-button"
		>
			{ props.children || __( 'Next', 'automatewoo' ) }
		</Button>
	);
}

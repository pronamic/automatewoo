/**
 * External dependencies
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import { Placeholder, Button } from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@woocommerce/blocks-checkout'; // eslint-disable-line import/no-unresolved -- This is DEWPed from WooCommerce Blocks.
/**
 * Internal dependencies
 */
import './editor.scss';

const { optinEnabled } = getSetting( 'automatewoo_data' );
const adminUrl = getSetting( 'adminUrl' );

/**
 * Renders a placeholder in the editor.
 */
const EmptyState = () => {
	return (
		<Placeholder
			icon={ <Icon icon={ megaphone } /> }
			label={ __( 'Marketing opt-in', 'automatewoo' ) }
			className="wp-block-automatewoo-marketing-optin-placeholder"
		>
			<span className="wp-block-automatewoo-marketing-optin-placeholder__description">
				{ __(
					'AutomateWoo marketing opt-in would be shown here if enabled. You can enable this from the settings page.',
					'automatewoo'
				) }
			</span>
			<Button
				isPrimary
				href={ `${ adminUrl }admin.php?page=automatewoo-settings` }
				target="_blank"
				rel="noopener noreferrer"
				className="wp-block-automatewoo-marketing-optin-placeholder__button"
			>
				{ __( 'Enable opt-in for Checkout', 'automatewoo' ) }
			</Button>
		</Placeholder>
	);
};

export const Edit = ( { attributes: { text }, setAttributes } ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			{ optinEnabled ? (
				<CheckboxControl
					id="automatewoo-marketing-optin"
					checked={ false }
					disabled={ true }
				>
					<RichText
						className="wc-block-components-checkbox__label"
						value={ text }
						onChange={ ( value ) =>
							setAttributes( { text: value } )
						}
					/>
				</CheckboxControl>
			) : (
				<EmptyState />
			) }
		</div>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};

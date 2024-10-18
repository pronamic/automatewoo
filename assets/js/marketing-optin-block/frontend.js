/* eslint-disable @woocommerce/dependency-group -- This rule reports false positive for inline comments, like below.*/
/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout'; // eslint-disable-line import/no-unresolved -- This is DEWPed from WooCommerce Blocks.
import { withFilteredAttributes } from '@woocommerce/shared-hocs'; // eslint-disable-line import/no-unresolved -- This is undocumented DEWPed internal detail of WooCommerce Blocks.
/**
 * Internal dependencies
 */
import metadata from './block.json';
import attributes from './attributes';
import FrontendBlock from './block';

registerCheckoutBlock( {
	metadata,
	component: withFilteredAttributes( attributes )( FrontendBlock ),
} );

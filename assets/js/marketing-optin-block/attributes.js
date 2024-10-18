/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const { optinDefaultText } = getSetting( 'automatewoo_data', '' );

export default {
	text: {
		type: 'string',
		default: optinDefaultText,
	},
};

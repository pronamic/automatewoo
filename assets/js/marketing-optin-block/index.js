/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Save, Edit } from './edit';
import metadata from './block.json';
import attributes from './attributes';

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
	attributes: {
		...metadata.attributes,
		...attributes,
	},
} );

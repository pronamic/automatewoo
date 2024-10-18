/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { AW_NAMESPACE } from '../constants';
import { updatePresets, setIsRequesting, setError } from './actions';

export function* getPresets() {
	yield setIsRequesting( 'getPresets', true );
	try {
		const url = AW_NAMESPACE + '/presets';
		const results = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		yield updatePresets( results );
	} catch ( error ) {
		yield setError( 'getPresets', error );
	}
}

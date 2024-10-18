/**
 * Copy of
 * - https://github.com/woocommerce/woocommerce/blob/3ff334bec9aa62d14f5ccf3f7beaa7451a11d665/plugins/woocommerce-admin/client/lib/async-requests/index.js#L24:L39
 * - https://github.com/woocommerce/google-listings-and-ads/blob/f0b37793b49d632bf96bbdcd2bb13f4e53898c7c/js/src/reports/products/async-requests.js#L28:L43
 */

/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { getIdsFromQuery } from '@woocommerce/navigation';

const identity = ( element ) => element;
/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path         - api path string or a function of the query returning api path string
 * @param {Function}        [handleData] - function applied to each iteration of data
 * @return {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function ( queryString = '', query ) {
		const pathString = typeof path === 'function' ? path( query ) : path;
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = {
			include: idList.join( ',' ),
			per_page: idList.length,
		};
		return apiFetch( {
			path: addQueryArgs( pathString, payload ),
		} ).then( ( data ) => data.map( handleData ) );
	};
}

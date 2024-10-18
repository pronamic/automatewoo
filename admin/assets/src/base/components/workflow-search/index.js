/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { handleFetchError } from '../../utils';

const WorkflowSearch = ( {
	label,
	requestParams = {},
	onChange,
	placeholder,
	selected,
} ) => {
	const [ options, setOptions ] = useState( [] );

	const onSearch = async ( prevOptions, query ) => {
		if ( ! query ) {
			return [];
		}

		try {
			const workflows = await apiFetch( {
				path: addQueryArgs( '/automatewoo/workflows', {
					...requestParams,
					search: query,
				} ),
			} );

			const newOptions = workflows.map( ( workflow ) => {
				return {
					key: workflow.id.toString(),
					label: workflow.title,
					value: workflow,
				};
			} );

			setOptions( newOptions );
			return newOptions;
		} catch ( error ) {
			handleFetchError(
				__(
					'There was an error when searching for workflows.',
					'automatewoo'
				),
				error
			);
			return [];
		}
	};

	return (
		<SelectControl
			isSearchable
			label={ label }
			onChange={ onChange }
			onSearch={ onSearch }
			options={ options }
			placeholder={ placeholder }
			searchDebounceTime={ 500 } // I'm not sure this prop actual debounces the search
			selected={ selected }
			showClearButton={ true } // Doesn't appear to be implemented
		/>
	);
};

WorkflowSearch.propTypes = {
	/**
	 * Params to add to the API request
	 */
	requestParams: PropTypes.object,
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * A placeholder for the search input.
	 */
	placeholder: PropTypes.string.isRequired,
	/**
	 * An array of objects describing selected values or optionally a string
	 * for a single value.
	 */
	selected: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.arrayOf(
			PropTypes.shape( {
				key: PropTypes.oneOfType( [
					PropTypes.number,
					PropTypes.string,
				] ).isRequired,
				label: PropTypes.string,
			} )
		),
	] ).isRequired,
};

export default WorkflowSearch;

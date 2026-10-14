jQuery( function ( $ ) {
	if ( typeof automatewoo_presubmit_params === 'undefined' ) {
		return false;
	}

	// eslint-disable-next-line no-undef -- Legacy global.
	const params = automatewoo_presubmit_params;

	let email = '';
	const $checkoutForm = $( 'form.checkout' );
	const emailFields = params.email_capture_selectors;
	const checkoutFields = params.checkout_capture_selectors;
	const checkoutFieldsData = {};
	const language = params.language;
	let captureEmailXhr;
	let captured = false;

	// A reloaded checkout can already hold an email, so field updates are allowed from the start.
	$( emailFields.join( ', ' ) ).each( function () {
		if ( $( this ).val() ) {
			captured = true;
		}
	} );

	$.each( checkoutFields, function ( i, fieldName ) {
		checkoutFieldsData[ fieldName ] = '';
	} );

	/**
	 * Get the current values for checkout fields.
	 *
	 * @return {Object} Checkout fields.
	 */
	function getCheckoutFieldValues() {
		const fields = {};

		$.each( checkoutFields, function ( i, fieldName ) {
			fields[ fieldName ] = $(
				'form.woocommerce-checkout [name="' + fieldName + '"]'
			).val();
		} );

		return fields;
	}

	function captureEmail() {
		if ( ! $( this ).val() || email === $( this ).val() ) {
			return;
		}

		email = $( this ).val();

		const data = {
			email,
			language,
			checkout_fields: getCheckoutFieldValues(),
		};

		if ( captureEmailXhr ) {
			captureEmailXhr.abort();
		}

		captureEmailXhr = $.post(
			params.ajax_url
				.toString()
				.replace( '%%endpoint%%', 'capture_email' ),
			data
		).done( function () {
			captured = true;
		} );
	}

	function captureCheckoutField() {
		const fieldName = $( this ).attr( 'name' );
		if (
			! captured ||
			! fieldName ||
			checkoutFields.indexOf( fieldName ) === -1
		) {
			return;
		}

		const fieldValue = $( this ).val();
		// Don't capture if the field is empty or hasn't changed
		if ( ! fieldValue || checkoutFieldsData[ fieldName ] === fieldValue ) {
			return;
		}

		checkoutFieldsData[ fieldName ] = fieldValue;

		$.post(
			params.ajax_url
				.toString()
				.replace( '%%endpoint%%', 'capture_checkout_field' ),
			{
				field_name: fieldName,
				field_value: fieldValue,
			}
		);
	}

	$( document ).on( 'blur change', emailFields.join( ', ' ), captureEmail );
	$checkoutForm.on( 'change', 'select', captureCheckoutField );
	$checkoutForm.on( 'blur change', '.input-text', captureCheckoutField );
} );

// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AutomateWoo, ClipboardJS, ajaxurl */
/**
 * AutomateWoo Variables
 */

jQuery( function ( $ ) {
	AutomateWoo.Variables = {
		$meta_box: $( '#aw_variables_box' ),

		init() {
			this.init_clipboard();

			$( document.body ).on(
				'change keyup',
				'.aw-workflow-variable-parameter',
				this.update_preview_field
			);
			$( document.body ).on(
				'keypress',
				'input.aw-workflow-variable-parameter',
				this.restrict_parameter_chars
			);

			this.$meta_box.on(
				'click',
				'.aw-workflow-variable',
				this.open_modal
			);
		},

		/**
		 */
		init_clipboard() {
			const clipboard = new ClipboardJS( '.aw-clipboard-btn', {
				text() {
					let text = $(
						'#aw_workflow_variable_preview_field'
					).text();

					$( '.aw-workflow-variable-parameter' ).each(
						function ( i, field ) {
							if (
								$( field ).prop( 'required' ) &&
								! $( field ).get( 0 ).checkValidity()
							) {
								$( field ).get( 0 ).reportValidity();
								text = false;

								return false;
							}
						}
					);

					return text;
				},
			} );

			clipboard.on( 'success', function () {
				$( '.aw-clipboard-btn' ).html( 'Copied!' );

				setTimeout( function () {
					AutomateWoo.Modal.close();
				}, 500 );
			} );
		},

		open_modal() {
			AutomateWoo.Modal.open();
			AutomateWoo.Modal.loading();

			const ajaxData = {
				action: 'aw_modal_variable_info',
				variable: $( this ).text(),
			};

			$.post( ajaxurl, ajaxData, function ( response ) {
				AutomateWoo.Modal.contents( response );
				AutomateWoo.Variables.update_preview_field();
			} );
		},

		/**
		 * Updates the variable preview text field
		 */
		update_preview_field() {
			const $previewField = $( '#aw_workflow_variable_preview_field' );
			const variable = $previewField.data( 'variable' );
			const parameters = [];

			$( '.aw-workflow-variable-parameter' ).each( function () {
				const $paramRow = $( this ).parents(
					'.aw-workflow-variables-parameter-row:first'
				);

				// Check 'show' logic
				if ( $paramRow.data( 'parameter-show' ) ) {
					const showLogic = $paramRow
						.data( 'parameter-show' )
						.split( '=' );

					const $conditionField = $(
						'.aw-workflow-variable-parameter[name="' +
							showLogic[ 0 ] +
							'"]'
					);

					if (
						$conditionField.length &&
						$conditionField.val() === showLogic[ 1 ]
					) {
						$paramRow.show();
					} else {
						$paramRow.hide();
						return; // don't add parameter to preview
					}
				}

				const param = {
					name: $( this ).attr( 'name' ),
					required: $paramRow.data( 'is-required' ),
					value: $( this ).val(),
				};

				parameters.push( param );
			} );

			const string = AutomateWoo.Variables.generate_variable_string(
				variable,
				parameters
			);

			$previewField.text( string );
		},

		/**
		 *
		 * @param {string}        variable
		 * @param {Array<Object>} parameters
		 */
		generate_variable_string( variable, parameters ) {
			let string = '{{ ' + variable;

			if ( parameters.length ) {
				const paramParts = [];

				$.each( parameters, function ( i, param ) {
					if ( param.value ) {
						paramParts.push(
							param.name + ": '" + param.value + "'"
						);
					} else if ( param.required ) {
						paramParts.push( param.name + ": '...'" );
					}
				} );

				if ( paramParts.length > 0 ) {
					string += ' | ';
					string += paramParts.join( ', ' );
				}
			}

			return string + ' }}';
		},

		/**
		 * @param {Event} event jQuery `keypress` event, with `which` property.
		 */
		restrict_parameter_chars( event ) {
			const restricted = [ 39, 123, 124, 125 ];

			if ( $.inArray( event.which, restricted ) !== -1 ) {
				return false;
			}
		},
	};

	AutomateWoo.Variables.init();
} );

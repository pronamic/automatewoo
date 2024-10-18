// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global _, automatewooValidateLocalizedErrorMessages, AW, tinyMCE */
/**
 * Workflow field validator
 */

( function ( $, localizedErrorMessages ) {
	const self = {
		errorMessages: {},

		init() {
			setInterval( function () {
				if ( typeof tinyMCE !== 'undefined' ) {
					tinyMCE.triggerSave();
				}

				$( '.automatewoo-field-wrap textarea.wp-editor-area' ).each(
					function () {
						$( this ).attr(
							'data-automatewoo-validate',
							'variables'
						);
						self.validateField( $( this ) );
					}
				);
			}, 2000 );

			$( document.body ).on(
				'keyup blur change',
				'[data-automatewoo-validate]',
				function ( event ) {
					self.validateField( $( event.target ) );
				}
			);

			self.validateAllFields();
		},

		validateAllFields() {
			$( '[data-automatewoo-validate]' ).each( function () {
				self.validateField( $( this ) );
			} );
		},

		validateField( $field ) {
			if ( ! AW.workflow ) {
				return;
			}

			const errors = [];
			const text = $field.val();

			self.clearFieldErrors( $field );

			const usedVariables = AW.Validate.getVariablesFromText( text );

			if ( self.fieldSupports( 'variables', $field ) ) {
				const trigger = AW.workflow.get( 'trigger' );

				_.each( usedVariables, function ( variable ) {
					const valid = self.isVariableValidForTrigger(
						variable,
						trigger
					);

					if ( valid !== true ) {
						errors.push(
							self.getErrorMessage(
								valid,
								self.getVariableWithoutParams( variable )
							)
						);
					}
				} );
			} else if ( usedVariables ) {
				errors.push( self.getErrorMessage( 'noVariablesSupport' ) );
			}

			if ( errors.length ) {
				self.setFieldErrors( $field, errors );
			}
		},

		setFieldErrors( $field, errors ) {
			$field.addClass( 'automatewoo-field--invalid' );
			const $wrap = $field.parents( '.automatewoo-field-wrap:first' );
			$wrap.append( '<div class="automatewoo-field-errors"></div>' );
			const $errors = $wrap.find( '.automatewoo-field-errors' );

			if ( $field.is( '.wp-editor-area' ) ) {
				$wrap
					.find( '.wp-editor-container' )
					.addClass( 'automatewoo-field--invalid' );
			}

			_.each( errors, function ( error ) {
				$errors.append(
					'<div class="automatewoo-field-errors__error">' +
						error +
						'</div>'
				);
			} );
		},

		clearFieldErrors( $field ) {
			const $wrap = $field.parents( '.automatewoo-field-wrap:first' );
			$field.removeClass( 'automatewoo-field--invalid' );

			if ( $field.is( '.wp-editor-area' ) ) {
				$wrap
					.find( '.wp-editor-container' )
					.removeClass( 'automatewoo-field--invalid' );
			}

			$wrap.find( '.automatewoo-field-errors' ).remove();
		},

		fieldSupports( option, $field ) {
			const options = $field.data( 'automatewoo-validate' ).split( ' ' );
			return _.indexOf( options, option ) !== -1;
		},

		/**
		 * @param {string} variable
		 * @param {Object} trigger  Workflow trigger.
		 * @return {boolean|string} `true` if valid, validation error otherwise.
		 */
		isVariableValidForTrigger( variable, trigger ) {
			if ( ! trigger ) {
				return 'noTrigger';
			}

			const dataType = self.getDataTypeFromVariable( variable );

			if (
				dataType &&
				_.indexOf( trigger.supplied_data_items, dataType ) === -1
			) {
				return 'invalidDataType';
			}

			const dataField = self.getDataFieldFromVariable( variable );
			const variables = AW.workflow.get( 'variables' );

			if ( variables && variables[ dataType ] ) {
				if ( variables[ dataType ].indexOf( dataField ) === -1 ) {
					return 'invalidVariable';
				}
			}

			return true;
		},

		/**
		 * Extract variables from a text field
		 *
		 * @param {string} text
		 * @return {Array<string>|false} Array of variables, or `false` if none.
		 */
		getVariablesFromText( text ) {
			const variables = text.match( /{{(.*?)}}/g );

			if ( ! variables ) {
				return false;
			}

			_.each( variables, function ( variable, i ) {
				variables[ i ] = variable.replace( /{|}/g, '' ).trim();
			} );

			return variables;
		},

		getVariableWithoutParams( variable ) {
			return variable.replace( /(\|.*)/, '' );
		},

		/**
		 * @param {string} variable
		 */
		getDataTypeFromVariable( variable ) {
			if ( variable.indexOf( '.' ) === -1 ) {
				return false;
			}
			return variable.replace( /(\..*)/, '' );
		},

		/**
		 * @param {string} variable
		 */
		getDataFieldFromVariable( variable ) {
			variable = self.getVariableWithoutParams( variable );
			const dotpos = variable.indexOf( '.' );
			if ( dotpos === -1 ) {
				return false;
			}
			return variable.substring( dotpos + 1 ).trim();
		},

		getErrorMessage( error, replace ) {
			if ( ! self.errorMessages[ error ] ) {
				return 'Unknown error, please try refreshing your browser.';
			}

			let message = self.errorMessages[ error ];

			if ( typeof replace === 'string' ) {
				message = message.replace( '%s', replace );
			}

			return message;
		},
	};

	AW.Validate = self;
	self.errorMessages = localizedErrorMessages;
} )( jQuery, automatewooValidateLocalizedErrorMessages );

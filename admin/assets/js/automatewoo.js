/**
 * AutomateWoo main - loaded on every admin page
 */
// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global automatewooLocalizeScript, ajaxurl */

const AutomateWoo = {},
	AW = {};

window.AutomateWoo = AutomateWoo;

( function ( $ ) {
	AW.init = function () {
		AW.params = automatewooLocalizeScript;

		AW.initTooltips();
		AW.initWorkflowStatusSwitch();
		AW.initShowHide();
		AW.initHoverableDates();
		AW.initBeforeAfterDayField();

		$( document.body ).on( 'wc-enhanced-select-init', function () {
			AW.initEnhancedSelects();
		} );

		$( document.body ).on(
			'automatewoo_trigger_changed',
			AW.initBeforeAfterDayField
		);
	};

	/**
	 * Init tool tips
	 */
	AW.initTooltips = function () {
		$( '.automatewoo-help-tip, .automatewoo-tiptip' ).tipTip( {
			attribute: 'data-tip',
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
		} );
	};

	/**
	 * Ajax search search box
	 */
	AW.initEnhancedSelects = function () {
		$( 'select.automatewoo-json-search' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const select2Args = {
					allowClear: $( this ).data( 'allow_clear' ) ? true : false,
					placeholder: $( this ).data( 'placeholder' ),
					minimumInputLength: '1',
					escapeMarkup( m ) {
						return m;
					},
					ajax: {
						url: AW.params.url.ajax,
						dataType: 'json',
						quietMillis: 250,
						data( params ) {
							const data = {
								term: params.term,
								action: $( this ).data( 'action' ),
							};

							// pass in sibling field data
							const sibling = $( this ).data( 'pass-sibling' );
							if ( sibling ) {
								const $sibling = $(
									'[name="' + sibling + '"]'
								);

								if ( $sibling.length ) {
									data.sibling = $sibling.val();
								}
							}

							return data;
						},
						processResults( data ) {
							const terms = [];
							if ( data ) {
								$.each( data, function ( id, text ) {
									terms.push( { id, text } );
								} );
							}
							return {
								results: terms,
							};
						},
						cache: true,
					},
				};

				$( this ).select2( select2Args ).addClass( 'enhanced' );
			} );
	};

	AW.initBeforeAfterDayField = function () {
		$( '.automatewoo-before-after-day-field-group__field--type' )
			.on( 'change', function () {
				const $type = $( this );
				const $days = $type.siblings(
					'.automatewoo-before-after-day-field-group__field--days'
				);

				if ( $type.val() === 'on_the_day' ) {
					$days.hide();
				} else {
					$days.show();
				}
			} )
			.trigger( 'change' );
	};

	AW.initWorkflowStatusSwitch = function () {
		$( '.aw-switch.js-toggle-workflow-status' ).on( 'click', function () {
			const $switch = $( this );

			if ( $switch.is( '.aw-loading' ) ) {
				return;
			}

			const state = $switch.attr( 'data-aw-switch' );
			const newState = state === 'on' ? 'off' : 'on';

			$switch.addClass( 'aw-loading' );
			$switch.attr( 'data-aw-switch', newState );

			$.post(
				ajaxurl,
				{
					action: 'aw_toggle_workflow_status',
					workflow_id: $switch.attr( 'data-workflow-id' ),
					new_state: newState,
					nonce: AW.params.nonces.aw_toggle_workflow_status,
				},
				function () {
					$switch.removeClass( 'aw-loading' );
				}
			);
		} );
	};

	/**
	 * @param {number} float
	 * @return {string} Formatted price with the currency symbol.
	 */
	AW.price = function ( float ) {
		let price = float
			.toFixed( 2 )
			.replace( '.', AW.params.locale.currency_decimal_separator )
			.replace(
				/\d(?=(\d{3})+(\D|$))/g,
				'$&' + AW.params.locale.currency_thousand_separator
			);
		const symbol = AW.params.locale.currency_symbol;

		switch ( AW.params.locale.currency_position ) {
			case 'right':
				price = price + symbol;
				break;
			case 'right_space':
				price = price + ' ' + symbol;
				break;
			case 'left':
				price = symbol + price;
				break;
			case 'left_space':
			default:
				price = symbol + ' ' + price;
				break;
		}

		return price;
	};

	AW.block = function ( $el ) {
		$el.block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		} );
	};

	/**
	 * Show / hide logic with data attributes
	 */
	AW.initShowHide = function () {
		const update = function ( $el ) {
			const id = $el.data( 'automatewoo-bind' );
			const value = $el.val();
			const isCheckbox = $el.is( 'input[type="checkbox"]' );

			$( '[data-automatewoo-show]' ).each( function () {
				if (
					isCheckbox &&
					$( this ).data( 'automatewoo-show' ) === id
				) {
					if ( $el.is( ':checked' ) ) {
						$( this ).show();
					} else {
						$( this ).hide();
					}
				} else {
					const logic = $( this )
						.data( 'automatewoo-show' )
						.split( '=' );

					if ( logic[ 0 ] !== id ) {
						return;
					}

					const possibleValues = logic[ 1 ].split( '|' );

					if ( possibleValues.indexOf( value ) !== -1 ) {
						$( this ).show();
					} else {
						$( this ).hide();
					}
				}
			} );

			$( '[data-automatewoo-hide]' ).each( function () {
				if (
					isCheckbox &&
					$( this ).data( 'automatewoo-hide' ) === id
				) {
					if ( $el.is( ':checked' ) ) {
						$( this ).hide();
					} else {
						$( this ).show();
					}
				} else {
					const logic = $( this )
						.data( 'automatewoo-hide' )
						.split( '=' );

					if ( logic[ 0 ] !== id ) {
						return;
					}

					const possibleValues = logic[ 1 ].split( '|' );

					if ( possibleValues.indexOf( value ) !== -1 ) {
						$( this ).hide();
					} else {
						$( this ).show();
					}
				}
			} );
		};

		$( document ).on( 'change', '[data-automatewoo-bind]', function () {
			update( $( this ) );
		} );

		$( '[data-automatewoo-bind]' ).each( function () {
			update( $( this ) );
		} );
	};

	AW.initHoverableDates = function () {
		const selector = '.automatewoo-hoverable-date';

		$( document.body )
			.on( 'mouseenter', selector, function () {
				$( this ).text( $( this ).data( 'automatewoo-date-no-diff' ) );
			} )
			.on( 'mouseleave', selector, function () {
				$( this ).text(
					$( this ).data( 'automatewoo-date-with-diff' )
				);
			} );
	};

	$( function () {
		AW.init();
	} );
} )( jQuery );

jQuery( function ( $ ) {
	Object.assign( AutomateWoo, {
		_email_preview_window: null,

		init() {
			this.init_notice_dismiss();
			this.init_date_pickers();
		},

		notices: {
			success( message, $location ) {
				if ( ! $location.length ) {
					return;
				}
				$location.before(
					'<div class="automatewoo-notice updated fade"><p><strong>' +
						message +
						'</strong></p></div>'
				);
			},

			error( message, $location ) {
				if ( ! $location.length ) {
					return;
				}
				$location.before(
					'<div class="automatewoo-notice error fade"><p><strong>' +
						message +
						'</strong></p></div>'
				);
			},

			clear_all() {
				$( '.automatewoo-notice' ).slideUp();
			},
		},

		init_notice_dismiss() {
			$( '.aw-notice-system-error' ).on(
				'click',
				'.notice-dismiss',
				function () {
					$.ajax( {
						url: ajaxurl,
						data: {
							action: 'aw_dismiss_system_error_notice',
							nonce: AW.params.nonces
								.aw_dismiss_system_error_notice,
						},
					} );
				}
			);

			$( '[data-automatewoo-dismissible-notice]' ).on(
				'click',
				'.notice-dismiss',
				function () {
					const $notice = $( this ).parents(
						'[data-automatewoo-dismissible-notice]'
					);

					$.post( {
						url: ajaxurl,
						data: {
							action: 'automatewoo_remove_notice',
							notice: $notice.data(
								'automatewoo-dismissible-notice'
							),
							nonce: AW.params.nonces.remove_notice,
						},
					} );
				}
			);
		},

		init_date_pickers() {
			$( '.automatewoo-date-picker' ).datepicker( {
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
			} );
		},

		isEmailPreviewOpen() {
			return (
				this._email_preview_window &&
				! this._email_preview_window.closed
			);
		},

		openLoadingEmailPreview() {
			this.openPreviewWindow(
				AW.params.url.admin +
					'admin.php?page=automatewoo-preview&action=loading'
			);
		},

		/**
		 * @param {*} type
		 * @param {*} args
		 */
		open_email_preview( type, args ) {
			const request = {
				page: 'automatewoo-preview',
				action: 'preview-ui',
				type,
				args,
			};

			this.openPreviewWindow(
				AW.params.url.admin + 'admin.php?' + $.param( request )
			);
		},

		/**
		 * @param {string | URL | undefined} url `window.open`'s url argument.
		 */
		openPreviewWindow( url ) {
			this._email_preview_window = window.open(
				url,
				'automatewoo_preview',
				'titlebar=no,toolbar=no,height=768,width=860,resizable=yes,status=no'
			);
		},
	} );

	AutomateWoo.init();
} );

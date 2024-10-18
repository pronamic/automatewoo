// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global $metabox, _, ajaxurl, AutomateWoo, automatewooWorkflowLocalizeScript, AW, Backbone, Cookies, quicktags, tinyMCE */
// Especially those seem pretty leaky, and dengerous.
// eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars
/* global canvas, name, settings, html, theButtons, use, edButtons, inst, qt */

/**
 * AutomateWoo Workflows Admin
 *
 * @param {Function} $            jQuery.
 * @param {Object}   workflowData automatewooWorkflowLocalizeScript
 */
( function ( $, workflowData ) {
	AW.Workflow = Backbone.Model.extend( {
		getAction( actionName ) {
			const actions = AW.workflow.get( 'actions' );

			if ( actions[ actionName ] ) {
				return actions[ actionName ];
			}
		},
	} );

	AW.WorkflowView = Backbone.View.extend( {
		el: $( 'form#post' ),

		$triggerSelect: $( '.js-trigger-select' ),

		$triggerDescription: $( '.js-trigger-description' ),

		$manualTriggerSelect: $( '.js-manual-trigger-select' ),

		$typeSelect: $( '.automatewoo-workflow-type-field' ),

		initialize() {
			this.listenTo( this.model, 'change:trigger', this.changeTrigger );

			this.model.set( 'prevTrigger', this.$triggerSelect.val() );
			this.model.set(
				'prevManualTrigger',
				this.$manualTriggerSelect.val()
			);
			this.model.set( 'prevType', this.$typeSelect.val() );

			this.setTriggerOptions();
			this.insertMetaboxHelpTips();
		},

		changeTrigger() {
			AW.rules.resetAvailableRules();

			$( document.body ).trigger( 'wc-enhanced-select-init' );
			AW.initTooltips();
			AW.Validate.validateAllFields();

			if ( AutomateWoo.Workflows.trigger_compatibility_warning() ) {
				AW.workflowView.completeTriggerChange();
			}
		},

		completeTriggerChange() {
			AW.rules.clearIncompatibleRules();
			AutomateWoo.Workflows.clearIncompatibleActions();

			AutomateWoo.Modal.close();

			AutomateWoo.Workflows.refine_variables();
			AutomateWoo.Workflows.refine_action_selects();
			AutomateWoo.Workflows.maybe_disable_queueing();

			this.updateTriggerDescription();

			// update the prev trigger, manual trigger, and type values
			this.model.set( 'prevTrigger', this.$triggerSelect.val() );
			this.model.set(
				'prevManualTrigger',
				this.$manualTriggerSelect.val()
			);
			this.model.set( 'prevType', this.$typeSelect.val() );

			$( document.body ).trigger( 'automatewoo_trigger_changed' );
		},

		cancelTriggerChange() {
			if ( this.$typeSelect.val() !== this.model.get( 'prevType' ) ) {
				this.$typeSelect
					.val( this.model.get( 'prevType' ) )
					.trigger( 'change' );
			}

			this.$triggerSelect
				.val( this.model.get( 'prevTrigger' ) )
				.trigger( 'change', true );

			this.$manualTriggerSelect
				.val( this.model.get( 'prevManualTrigger' ) )
				.trigger( 'change' );
		},

		setTriggerOptions() {
			const options = [];

			$( '[name^="aw_workflow_data[trigger_options]"]' ).each(
				function () {
					if ( $( this ).attr( 'type' ) === 'checkbox' ) {
						options.push( {
							name: $( this ).attr( 'name' ),
							value: $( this ).is( ':checked' ),
						} );
					} else {
						options.push( {
							name: $( this ).attr( 'name' ),
							text: $( this ).text(),
							value: $( this ).val(),
						} );
					}
				}
			);

			this.model.set( 'prevTriggerOptions', options );
		},

		/**
		 * Update the trigger description
		 */
		updateTriggerDescription() {
			const trigger = this.model.get( 'trigger' );

			if ( trigger && trigger.description ) {
				this.$triggerDescription.html(
					'<p class="aw-field-description">' +
						trigger.description +
						'</p>'
				);
			} else {
				this.$triggerDescription.html( '' );
			}
		},

		initAction( actionName, $action ) {
			const data = this.model.getAction( actionName );

			if ( data ) {
				$action.attr( 'data-automatewoo-action-name', actionName );
				$action.attr( 'data-automatewoo-action-group', data.group );
				$action.attr(
					'data-automatewoo-action-can-be-previewed',
					data.can_be_previewed
				);
			} else {
				$action.removeAttr( 'data-automatewoo-action-name' );
				$action.removeAttr( 'data-automatewoo-action-group' );
				$action.removeAttr(
					'data-automatewoo-action-can-be-previewed'
				);
			}

			// update dynamic fields
			$action
				.find( '[data-automatewoo-dynamic-select]' )
				.each( function ( i, el ) {
					AW.workflowView.initDynamicActionSelect( $( el ), $action );
				} );

			AW.initTooltips();
			AutomateWoo.Workflows.action_dependent_fields( $action );
			AutomateWoo.init_date_pickers();

			// Expand hidden action fields if required fields are empty when the form is submitted.
			$action.find( '.automatewoo-field' ).on( 'invalid', function () {
				const actionsBox = $( '#aw_actions_box' );
				const actionRow = $( this ).parents( '.automatewoo-action' );

				if ( actionsBox.hasClass( 'closed' ) ) {
					actionsBox.find( '.handlediv' ).trigger( 'click' );
				}

				if ( ! actionRow.hasClass( 'js-open' ) ) {
					AutomateWoo.Workflows.action_edit_open( actionRow );
					this.scrollIntoView();
				}
			} );
		},

		initDynamicActionSelect( $field, $action ) {
			const referenceFieldName = $field.attr(
				'data-automatewoo-dynamic-select-reference'
			);
			const $referenceField = $action.find(
				'.automatewoo-field[data-name="' + referenceFieldName + '"]'
			);

			$referenceField.on( 'change', function () {
				AW.workflowView.updateDynamicActionSelect(
					$field,
					$referenceField,
					$action
				);
			} );
		},

		updateDynamicActionSelect( $field, $referenceField, $action ) {
			if ( $field.is( '.automatewoo-field--loading' ) ) {
				return;
			}

			// remove existing options
			$field.find( "option[value!='']" ).remove();

			if ( ! $referenceField.val() ) {
				return;
			}

			const $fieldRow = $field.parents( '.automatewoo-table__row' );
			$fieldRow.addClass( 'automatewoo-field-row--loading' );

			const data = {
				action: 'aw_update_dynamic_action_select',
				action_name: $action.data( 'automatewoo-action-name' ),
				target_field_name: $field.attr( 'data-name' ),
				reference_field_value: $referenceField.val(),
				nonce: AW.workflow.get( 'nonces' )
					.aw_update_dynamic_action_select,
			};

			$.post( ajaxurl, data, function ( response ) {
				$fieldRow.removeClass( 'automatewoo-field-row--loading' );

				if ( response.success ) {
					$.each( response.data, function ( value, text ) {
						$field.append(
							$( '<option/>', {
								value,
								text,
							} )
						);
					} );
				}
			} );
		},

		initTrigger() {
			const $triggerBox = $( '#aw_trigger_box' );
			$triggerBox
				.find( '[data-automatewoo-dynamic-select]' )
				.each( function ( i, el ) {
					AW.workflowView.initDynamicTriggerOptions(
						$( el ),
						$triggerBox
					);
				} );

			$( '[name^="aw_workflow_data[trigger_options]"]' ).on(
				'change',
				() => {
					this.setTriggerOptions();
				}
			);
		},

		initDynamicTriggerOptions( $field, $triggerBox ) {
			const referenceFieldName = $field.attr(
				'data-automatewoo-dynamic-select-reference'
			);
			const $referenceField = $triggerBox.find(
				'.automatewoo-field[data-name="' + referenceFieldName + '"]'
			);

			$referenceField.on( 'change', function () {
				AW.workflowView.updateDynamicTriggerOptions(
					$field,
					$referenceField
				);
			} );
		},

		updateDynamicTriggerOptions( $field, $referenceField ) {
			if ( $field.is( '.automatewoo-field--loading' ) ) {
				return;
			}

			// remove existing options
			$field.empty();

			if ( ! $referenceField.val() ) {
				return;
			}

			const $fieldRow = $field.parents( '.automatewoo-table__row' );
			$fieldRow.addClass( 'automatewoo-field-row--loading' );

			const data = {
				action: 'aw_update_dynamic_trigger_options_select',
				trigger_name: $(
					'#aw_trigger_box select[name="aw_workflow_data[trigger_name]"]'
				).val(),
				target_field_name: $field.attr( 'data-name' ),
				reference_field_value: $referenceField.val(),
				nonce: AW.workflow.get( 'nonces' )
					.aw_update_dynamic_trigger_options_select,
			};

			$.post( ajaxurl, data, function ( response ) {
				if ( response.success ) {
					const options = $.map(
						response.data,
						function ( text, value ) {
							return $( '<option/>', {
								value,
								text,
							} );
						}
					);

					if ( options && options.length ) {
						$field.append( options );
					}
				}

				$fieldRow.removeClass( 'automatewoo-field-row--loading' );
			} );
		},

		/**
		 * Add helplinks to top of meta box
		 */
		insertMetaboxHelpTips() {
			const tips = this.model.get( 'metaBoxHelpTips' );

			_.each( tips, function ( tipHtml, id ) {
				$metabox = $( '#aw_' + id );
				$metabox.find( 'h2.hndle' ).append( tipHtml );
			} );
		},
	} );

	AW.TriggerCompatibilityModalView = Backbone.View.extend( {
		className: 'aw-view-trigger-compatibility-modal',

		template: wp.template( 'aw-trigger-compatibility-modal' ),

		data: null,

		initialize( data ) {
			this.data = data;

			document.body.addEventListener(
				'awmodal-close',
				( event ) => {
					if ( event.detail.closedBy === 'dismiss' ) {
						AW.workflowView.cancelTriggerChange();
					}
				},
				{ once: true }
			);

			this.$el.on( 'click', '.js-confirm', function () {
				AW.workflowView.completeTriggerChange();
			} );
		},

		render() {
			this.$el.html( this.template( this.data ) );
			return this;
		},
	} );

	AW.TriggerPresetActivationModalView = Backbone.View.extend( {
		className: 'aw-view-trigger-preset-activation-modal',

		template: wp.template( 'aw-trigger-preset-activation-modal' ),

		data: null,

		initialize( data ) {
			this.data = data;

			this.$el.on( 'click', '.js-confirm', () => {
				this.disableButtons();

				// Close with applicable tracking action set up.
				data.action = 'confirm';
				AutomateWoo.Modal.close();

				// submit
				$( AW.workflowView.el )
					.data( 'aw-preset-workflow-confirmed', true )
					.trigger( 'submit' );
			} );

			this.$el.on(
				'click',
				`.${ AutomateWoo.Modal.triggerClasses.close }`,
				() => {
					data.action = 'cancel';

					this.disableButtons();
				}
			);
		},

		disableButtons() {
			this.$el
				.find( '.automatewoo-modal__footer button' )
				.addClass( 'disabled' );
		},

		render() {
			this.$el.html( this.template( this.data ) );
			return this;
		},

		/**
		 * Opens `AutomateWoo.Modal`, fills it with rendered message.
		 * Record Tracking events `preset_activation_alert_rendered` when executed,
		 * and 'preset_activation_alert_closed' with applicable `action` and `is_active` once eventually closed.
		 */
		open() {
			// Once the modal is open, the default close action for built-in Modal close features is 'dismiss'.
			this.data.action = 'dismiss';

			AutomateWoo.Modal.open();
			AutomateWoo.Modal.contents( this.render().el );

			AW.tracks.recordEvent( 'preset_activation_alert_rendered', {
				is_active: this.data.isActive,
			} );

			// If the modal is dismissed by AutomateWoo.Modal features.
			document.body.addEventListener(
				'awmodal-close',
				() =>
					AW.tracks.recordEvent( 'preset_activation_alert_closed', {
						is_active: this.data.isActive,
						action: this.data.action,
					} ),
				{ once: true }
			);
		},
	} );

	AW.workflow = new AW.Workflow( workflowData );

	AW.workflowView = new AW.WorkflowView( {
		model: AW.workflow,
	} );

	AW.Validate.init();
} )( jQuery, automatewooWorkflowLocalizeScript );

jQuery( function ( $ ) {
	AutomateWoo.Workflows = {
		$triggers_box: $( '#aw_trigger_box' ),
		$manual_workflow_box: $( '#aw_manual_workflow_box' ),

		$actions_box: $( '#aw_actions_box' ),

		$actions_container: $( '.aw-actions-container' ),

		$action_template: $( '.aw-action-template' ),

		$trigger_select: $( '.js-trigger-select' ).first(),
		$manual_trigger_select: $( '.js-manual-trigger-select' ).first(),

		init() {
			AutomateWoo.Workflows.init_triggers_box();
			AutomateWoo.Workflows.init_actions_box();
			AutomateWoo.Workflows.init_options_box();
			AutomateWoo.Workflows.refine_variables();
		},

		/**
		 */
		init_triggers_box() {
			AutomateWoo.Workflows.$trigger_select.on(
				'change',
				function ( event, restoreOptions ) {
					AutomateWoo.Workflows.fill_trigger_fields(
						$( this ).val(),
						restoreOptions
					);
				}
			);

			AutomateWoo.Workflows.$manual_trigger_select.on(
				'change',
				function () {
					AutomateWoo.Workflows.fill_manual_workflow_trigger_fields(
						$( this ).val()
					);
				}
			);

			AW.workflowView.initTrigger();
		},

		/**
		 */
		init_actions_box() {
			$( '.automatewoo-action.js-open' ).each( function () {
				AutomateWoo.Workflows.action_edit_open( $( this ) );
			} );

			$( '.js-aw-add-action' ).on( 'click', function ( e ) {
				e.preventDefault();
				AutomateWoo.Workflows.add_new_action();
			} );

			$( document ).on( 'click', '.js-edit-action', function ( e ) {
				e.preventDefault();

				const $action = $( this )
					.parents( '.automatewoo-action' )
					.first();

				if ( $action.is( '.js-open' ) ) {
					AutomateWoo.Workflows.action_edit_close( $action );
				} else {
					AutomateWoo.Workflows.action_edit_open( $action );
				}
			} );

			$( document ).on( 'click', '.js-delete-action', function ( e ) {
				e.preventDefault();
				const $action = $( this )
					.parents( '.automatewoo-action' )
					.first();
				AutomateWoo.Workflows.action_delete( $action );
			} );

			// Action select change
			$( document ).on( 'change', '.js-action-select', function () {
				const $action = $( this )
					.parents( '.automatewoo-action' )
					.first();
				AutomateWoo.Workflows.fill_action_fields(
					$action,
					$( this ).val()
				);
			} );

			// preview links
			$( document ).on(
				'click',
				'[data-automatewoo-preview]',
				function ( e ) {
					e.preventDefault();
					const $action = $( this )
						.parents( '.automatewoo-action' )
						.first();
					AutomateWoo.Workflows.preview_action( $action );
				}
			);

			if ( ! AW.workflow.get( 'isNew' ) ) {
				AutomateWoo.Workflows.refine_action_selects();
				AutomateWoo.Workflows.refine_variables();
				AutomateWoo.Workflows.maybe_disable_queueing();

				$( '.automatewoo-action' ).each( function ( i, el ) {
					AW.workflowView.initAction(
						$( el ).find( '.js-action-select' ).val(),
						$( el )
					);
				} );
			}
		},

		init_options_box() {
			const $checkboxClickTracking = $(
				'.aw-checkbox-enable-click-tracking'
			);

			AutomateWoo.Workflows.maybe_hide_tracking_options();

			$checkboxClickTracking.on( 'click', function () {
				AutomateWoo.Workflows.maybe_hide_tracking_options();
			} );
		},

		maybe_hide_tracking_options() {
			const $checkboxClickTracking = $(
				'.aw-checkbox-enable-click-tracking'
			);
			const checked = $checkboxClickTracking.is( ':checked' );

			if ( ! checked ) {
				$( '.js-require-email-tracking' ).hide();
			} else {
				$( '.js-require-email-tracking' ).show();
			}
		},

		/**
		 * @param {*}       triggerName
		 * @param {boolean} [restoreOptions=false] If true then the trigger fields will be populated from the previous trigger options
		 */
		fill_trigger_fields( triggerName, restoreOptions = false ) {
			// Remove existing fields
			AutomateWoo.Workflows.$triggers_box
				.find( 'tr.aw-trigger-option' )
				.remove();

			if ( triggerName ) {
				AutomateWoo.Workflows.$triggers_box.addClass( 'aw-loading' );

				this.fetch_trigger_data( triggerName ).done(
					function ( response ) {
						if ( ! response.success ) {
							return;
						}

						AutomateWoo.Workflows.$triggers_box
							.find( 'tbody' )
							.append( response.data.fields );
						AutomateWoo.Workflows.$triggers_box.removeClass(
							'aw-loading'
						);

						if ( restoreOptions ) {
							const options =
								AW.workflow.get( 'prevTriggerOptions' );

							$.each( options, function ( index, option ) {
								const element = $(
									`[name="${ option.name }"]`
								);

								switch ( element.prop( 'type' ) ) {
									case 'select-one':
										if (
											element.find( 'option' ).length ===
											0
										) {
											const selected = $( '<option />' )
												.attr( 'value', option.value )
												.text( option.text );

											element.append( selected );
										} else {
											element.val( option.value );
										}
										break;
									case 'checkbox':
										element.prop( 'checked', option.value );
										break;
									default:
										element.val( option.value );
										break;
								}

								element.trigger( 'change' );
							} );
						}

						AW.workflow.set( 'trigger', response.data.trigger );
						AW.workflowView.initTrigger();
					}
				);
			} else {
				AW.workflow.set( 'trigger', false );
			}
		},

		fetch_trigger_data( triggerName ) {
			return $.ajax( {
				method: 'GET',
				url: ajaxurl,
				data: {
					action: 'aw_fill_trigger_fields',
					trigger_name: triggerName,
					workflow_id: AW.workflow.get( 'id' ),
					is_new_workflow: AW.workflow.get( 'isNew' ),
					nonce: AW.workflow.get( 'nonces' ).aw_fill_trigger_fields,
				},
			} );
		},

		/**
		 * @param {*} triggerName
		 */
		fill_manual_workflow_trigger_fields( triggerName ) {
			const $metabox = AutomateWoo.Workflows.$manual_workflow_box;

			// Remove existing fields
			$metabox.find( 'tr.aw-trigger-option' ).remove();

			if ( triggerName ) {
				$metabox.addClass( 'aw-loading' );

				this.fetch_trigger_data( triggerName ).done(
					function ( response ) {
						if ( ! response.success ) {
							return;
						}

						$metabox.find( 'tbody' ).append( response.data.fields );
						$metabox.removeClass( 'aw-loading' );

						AW.workflow.set( 'trigger', response.data.trigger );
					}
				);
			} else {
				AW.workflow.set( 'trigger', false );
			}
		},

		add_new_action() {
			const actionNumber =
				AutomateWoo.Workflows.get_number_of_actions() + 1;

			$( '.js-aw-no-actions-message' ).hide();

			const $newAction = AutomateWoo.Workflows.$action_template.clone();
			$newAction.removeClass( 'aw-action-template' );
			$newAction.addClass( 'automatewoo-action' );

			AutomateWoo.Workflows.$actions_container.append( $newAction );

			$newAction.attr( 'data-action-number', actionNumber );

			AutomateWoo.Workflows.action_edit_open( $newAction );
		},

		/**
		 * Get the name for the cookie that stores if an action is being edited.
		 *
		 * @param {number} actionNumber
		 * @return {string} Prefixed cookie name `aw_editing_action_${ workflowId }_${ actionNumber }`.
		 */
		get_editing_action_cookie_name( actionNumber ) {
			const workflowId = AW.workflow.get( 'id' );
			return `aw_editing_action_${ workflowId }_${ actionNumber }`;
		},

		action_edit_open( $action ) {
			const actionNumber = $action.data( 'action-number' );

			$action.addClass( 'js-open' );
			$action.find( '.automatewoo-action__fields' ).slideDown( 150 );

			AW.initTooltips();

			// save open state
			Cookies.set(
				this.get_editing_action_cookie_name( actionNumber ),
				1,
				{ sameSite: 'strict' }
			);
		},

		/**
		 * Set up fields that are dependent on each other.
		 *
		 * @param {jQuery} $action
		 */
		action_dependent_fields( $action ) {
			const $hideWhenChecked = $action.find( '[data-hide-when-checked]' );
			$hideWhenChecked.each( function () {
				const $this = $( this );
				const $dependency = $this.data( 'hideWhenChecked' );
				const $checkbox = $( `[data-name="${ $dependency }"]` ).find(
					'input[type="checkbox"]'
				);

				if ( ! $checkbox ) {
					return;
				}
				const $parentRow = $this.closest( 'tr.automatewoo-table__row' );

				$checkbox
					.on( 'change', function () {
						if ( this.checked ) {
							$parentRow.hide();
						} else {
							$parentRow.show();
						}
					} )
					.trigger( 'change' );
			} );
		},

		action_edit_close( $action ) {
			const actionNumber = $action.data( 'action-number' );

			$action.removeClass( 'js-open' );
			$action.find( '.automatewoo-action__fields' ).slideUp( 150 );

			Cookies.remove(
				this.get_editing_action_cookie_name( actionNumber )
			);
		},

		/**
		 * @param {jQuery} $action
		 */
		action_delete( $action ) {
			$action.remove();
		},

		/**
		 * @param {jQuery} $action
		 * @param {string} selectedAction
		 */
		fill_action_fields( $action, selectedAction ) {
			const actionNumber = $action.data( 'action-number' );
			const $select = $action.find( '.js-action-select' );

			AutomateWoo.Workflows.$actions_box.addClass( 'aw-loading' );

			// Remove existing fields
			$action
				.find(
					'tr.automatewoo-table__row:not([data-name="action_name"])'
				)
				.remove();

			$.ajax( {
				method: 'GET',
				url: ajaxurl,
				data: {
					action: 'aw_fill_action_fields',
					action_name: selectedAction,
					action_number: actionNumber,
					workflow_id: AW.workflow.get( 'id' ),
					nonce: AW.workflow.get( 'nonces' ).aw_fill_action_fields,
				},
			} ).done( function ( response ) {
				$action
					.find( '.automatewoo-table tbody' )
					.append( response.data.fields );
				AutomateWoo.Workflows.$actions_box.removeClass( 'aw-loading' );

				// Fill select box name
				$select.attr(
					'name',
					'aw_workflow_data[actions][' +
						actionNumber +
						'][action_name]'
				);

				// Pre fill title
				$action.find( '.action-title' ).text( response.data.title );

				$action
					.find( '.js-action-description' )
					.html( response.data.description );

				AW.workflowView.initAction( selectedAction, $action );
			} );
		},

		get_number_of_actions() {
			return $( '.automatewoo-action' ).length;
		},

		/**
		 * Show or hide text var groups based on the selected trigger
		 */
		refine_variables() {
			const trigger = AW.workflow.get( 'trigger' );

			if ( ! trigger ) {
				$( '#aw_variables_box' ).hide();
			} else {
				$( '#aw_variables_box' ).show();
				$( '.aw-variables-group' ).each( function ( i, el ) {
					const group = $( el ).data( 'automatewoo-variable-group' );

					if (
						$.inArray( group, trigger.supplied_data_items ) === -1
					) {
						$( el ).hide();
					} else {
						$( el ).show();
					}
				} );
			}
		},

		/**
		 * Show or hide select options based on the selected trigger
		 * Also what if a trigger is changed after an action is already added
		 */
		refine_action_selects() {
			$( '.js-action-select' ).each( function () {
				$( this )
					.find( 'option' )
					.each( function () {
						$( this ).prop(
							'disabled',
							! AutomateWoo.Workflows.is_action_compatible_with_current_trigger(
								$( this ).val()
							)
						);
					} );
			} );
		},

		/**
		 * Hide queue if disabled for the selected trigger
		 */
		maybe_disable_queueing() {
			const trigger = AW.workflow.get( 'trigger' );

			if ( trigger && trigger.allow_queueing ) {
				$( '#aw_timing_box' ).show();
			} else {
				$( '#aw_timing_box' ).hide();
			}
		},

		/**
		 * Be sure to run this before refine_action_selects
		 *
		 * @return {boolean} `false` if switching back to stop the trigger change.
		 */
		trigger_compatibility_warning() {
			let incompatibleRules = [];
			let incompatibleActions = [];

			_.each( AW.rules.get( 'ruleOptions' ), function ( ruleGroup ) {
				_.each( ruleGroup.get( 'rules' ), function ( rule ) {
					if (
						rule.get( 'name' ) &&
						! AW.rules.isRuleAvailable( rule.get( 'name' ) )
					) {
						const ruleObject = rule.get( 'object' );
						incompatibleRules.push( ruleObject.title );
					}
				} );
			} );

			$( '.js-action-select' ).each( function () {
				if (
					! AutomateWoo.Workflows.is_action_compatible_with_current_trigger(
						$( this ).val()
					)
				) {
					incompatibleActions.push(
						$( this ).find( 'option:selected' ).text()
					);
				}
			} );

			if ( incompatibleRules.length || incompatibleActions.length ) {
				incompatibleActions = _.uniq( incompatibleActions );
				incompatibleRules = _.uniq( incompatibleRules );

				const modalView = new AW.TriggerCompatibilityModalView( {
					incompatibleRules,
					incompatibleActions,
				} );

				AutomateWoo.Modal.open();
				AutomateWoo.Modal.contents( modalView.render().el );

				return false;
			}

			return true;
		},

		clearIncompatibleActions() {
			$( '.js-action-select' ).each( function () {
				if (
					! AutomateWoo.Workflows.is_action_compatible_with_current_trigger(
						$( this ).val()
					)
				) {
					const $action = $( this )
						.parents( '.automatewoo-action' )
						.first();
					AutomateWoo.Workflows.action_delete( $action );
				}
			} );
		},

		/**
		 *
		 * @param {string} actionName
		 * @return {boolean} `true` is the action is compatible, `false` if it's not valid or compatible.
		 */
		is_action_compatible_with_current_trigger( actionName ) {
			let compatible = true;
			const action = AW.workflow.getAction( actionName );

			// Not a valid action
			if ( ! action ) {
				return true;
			}

			// No data items required
			if ( ! action.required_data_items.length ) {
				return true;
			}

			const trigger = AW.workflow.get( 'trigger' );

			$.each( action.required_data_items, function ( i, value ) {
				if ( $.inArray( value, trigger.supplied_data_items ) === -1 ) {
					compatible = false;
				}
			} );

			return compatible;
		},

		preview_action( $action ) {
			const actionNumber = $action.data( 'action-number' );
			const trigger = AW.workflow.get( 'trigger' );
			const fields = {};

			if ( AutomateWoo.isEmailPreviewOpen() ) {
				AutomateWoo.Workflows.$actions_box.addClass( 'aw-loading' );
			}

			if ( typeof tinyMCE !== 'undefined' ) {
				tinyMCE.triggerSave();
			}

			const nameSelector =
				'aw_workflow_data[actions][' + actionNumber + ']';

			// get fields to preview
			$action
				.find( '[name*="' + nameSelector + '"]' )
				.each( function ( i, el ) {
					let elementName, val;

					elementName = $( el ).attr( 'name' );
					const isGrouped = /\[]$/.test( elementName );

					if ( isGrouped ) {
						elementName = elementName.replace( '[]', '' );
					}

					// get the name
					const fieldName = elementName
						.replace( nameSelector, '' )
						.replace( '[', '' )
						.replace( ']', '' );

					if ( $( el ).attr( 'type' ) === 'checkbox' ) {
						val = el.checked ? '1' : '';
					} else {
						val = $( el ).val();
					}

					if ( isGrouped ) {
						if ( ! fields.hasOwnProperty( fieldName ) ) {
							fields[ fieldName ] = [];
						}
						fields[ fieldName ].push( val );
					} else {
						fields[ fieldName ] = val;
					}
				} );

			AutomateWoo.openLoadingEmailPreview(); // open the preview window before saving so that the popup is not blocked

			$.ajax( {
				method: 'POST',
				url: ajaxurl,
				data: {
					action: 'aw_save_preview_data',
					workflow_id: AW.workflow.get( 'id' ),
					trigger_name: trigger ? trigger.name : '',
					action_fields: fields,
					nonce: AW.workflow.get( 'nonces' ).aw_save_preview_data,
				},
				success() {
					AutomateWoo.open_email_preview( 'workflow_action', {
						workflow_id: AW.workflow.get( 'id' ),
						action_number: actionNumber,
					} );
				},
				complete() {
					AutomateWoo.Workflows.$actions_box.removeClass(
						'aw-loading'
					);
				},
			} );
		},

		/**
		 * @param {string} id
		 */
		init_ajax_wysiwyg( id ) {
			if (
				typeof tinymce === 'undefined' ||
				typeof tinyMCEPreInit.mceInit.automatewoo_editor === 'undefined'
			) {
				return;
			}

			const mceInit = $.extend(
				{},
				tinyMCEPreInit.mceInit.automatewoo_editor
			);
			const qtInit = $.extend(
				{},
				tinyMCEPreInit.qtInit.automatewoo_editor
			);

			mceInit.selector = '#' + id;
			mceInit.id = id;
			mceInit.wp_autoresize_on = false;

			tinyMCEPreInit.mceInit[ mceInit.id ] = mceInit;

			qtInit.id = id;

			const $wrap = tinymce.$( '#wp-' + id + '-wrap' );

			if (
				$wrap.hasClass( 'tmce-active' ) ||
				! tinyMCEPreInit.qtInit.hasOwnProperty( id )
			) {
				try {
					tinymce.init( mceInit );
				} catch ( e ) {}
			}

			try {
				const qtags = quicktags( qtInit );

				this.init_wysiwyg_buttons( qtags );
			} catch ( e ) {}
		},

		/**
		 *
		 * @param {Object} qtags
		 */
		init_wysiwyg_buttons( qtags ) {
			const defaults =
				',strong,em,link,block,del,ins,img,ul,ol,li,code,more,close,';

			canvas = qtags.canvas;
			name = qtags.name;
			settings = qtags.settings;
			html = '';
			theButtons = {};
			use = '';

			// set buttons
			if ( settings.buttons ) {
				use = ',' + settings.buttons + ',';
			}

			for ( const buttonIndex in edButtons ) {
				if ( ! edButtons[ buttonIndex ] ) {
					continue;
				}

				const id = edButtons[ buttonIndex ].id;
				if (
					use &&
					defaults.indexOf( ',' + id + ',' ) !== -1 &&
					use.indexOf( ',' + id + ',' ) === -1
				) {
					continue;
				}

				if (
					! edButtons[ buttonIndex ].instance ||
					edButtons[ buttonIndex ].instance === inst
				) {
					theButtons[ id ] = edButtons[ buttonIndex ];

					if ( edButtons[ buttonIndex ].html ) {
						html += edButtons[ buttonIndex ].html( name + '_' );
					}
				}
			}

			if ( use && use.indexOf( ',fullscreen,' ) !== -1 ) {
				theButtons.fullscreen = new qt.FullscreenButton();
				html += theButtons.fullscreen.html( name + '_' );
			}

			if ( document.getElementsByTagName( 'html' )[ 0 ].dir === 'rtl' ) {
				theButtons.textdirection = new qt.TextDirectionButton();
				html += theButtons.textdirection.html( name + '_' );
			}

			qtags.toolbar.innerHTML = html;
			qtags.theButtons = theButtons;
		},
	};

	AutomateWoo.Workflows.init();

	/**
	 * Customer win back trigger.
	 *
	 * Changes the max field placeholder so it's 3 days after the min field val.
	 */
	function initCustomerWinBackTrigger() {
		const $minField = $(
			'input[name="aw_workflow_data[trigger_options][days_since_last_purchase]"]'
		);
		const $maxField = $(
			'input[name="aw_workflow_data[trigger_options][days_since_last_purchase_max]"]'
		);
		const defaultRange = 3;

		$minField
			.on( 'change keyup', function () {
				const minVal = $minField.val()
					? parseInt( $minField.val(), 10 )
					: 0;
				let placeholder = '';

				if ( minVal ) {
					placeholder = minVal + defaultRange;
				}

				$maxField.attr( 'min', minVal + 1 );
				$maxField.attr( 'placeholder', placeholder );
			} )
			.trigger( 'change' );
	}

	$( document.body ).on( 'automatewoo_trigger_changed', function () {
		initCustomerWinBackTrigger();
	} );

	function initWorkflowType() {
		const $typeSelectField = $( '.automatewoo-workflow-type-field' );

		function updateType( type, doReset ) {
			if ( type === 'manual' ) {
				$(
					'#aw_trigger_box, #automatewoo-workflow-status-field-row'
				).hide();
				$( '#aw_manual_workflow_box' ).show();
				$( '#automatewoo-workflow-run-btn' )
					.show()
					.css( 'display', 'inline-block' );
			} else {
				$(
					'#automatewoo-workflow-run-btn, #aw_manual_workflow_box'
				).hide();
				$(
					'#aw_trigger_box, #automatewoo-workflow-status-field-row'
				).show();
			}

			if ( doReset ) {
				// Reset select box to first option on type change
				AutomateWoo.Workflows.$manual_trigger_select[ 0 ].selectedIndex = 0;
				AutomateWoo.Workflows.$trigger_select[ 0 ].selectedIndex = 0;

				AutomateWoo.Workflows.$triggers_box
					.find( 'tr.aw-trigger-option' )
					.remove();

				AW.workflow.set( 'trigger', false );
			}
		}

		$typeSelectField.on( 'change', function () {
			updateType( $typeSelectField.val(), true );
		} );

		updateType( $typeSelectField.val(), false );
	}

	$( '#automatewoo-workflow-run-btn' ).on( 'click', function () {
		$( 'input[name="automatewoo_redirect_to_runner"]' ).val( 1 );
		$( '#publish' ).trigger( 'click' );
		return false;
	} );

	$( 'form#post' ).on( 'submit', function () {
		const $form = $( this );
		const isActive =
			$( 'select[name=workflow_status]', $form ).val() === 'active';
		const isConfirmed = $form.data( 'aw-preset-workflow-confirmed' );
		const isFirstPresetSave = /workflow-origin=preset/.test(
			window.location.href
		);

		if ( isFirstPresetSave && ! isConfirmed ) {
			const activationModalView = new AW.TriggerPresetActivationModalView(
				{ isActive }
			);
			activationModalView.open();

			return false;
		}

		return true;
	} );

	initCustomerWinBackTrigger();
	initWorkflowType();
} );

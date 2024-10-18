// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AW, Backbone, _, ajaxurl, automatewooWorkflowLocalizeScript */
( function ( $, data ) {
	// MODELS

	AW.Rules = Backbone.Model.extend( {
		initialize() {
			const ruleOptions = [];

			if ( this.get( 'rawRuleOptions' ) ) {
				// convert rule options from json to models

				_.each( this.get( 'rawRuleOptions' ), ( rawRuleGroup ) => {
					const group = new AW.RuleGroup( this );
					const rules = [];

					_.each( rawRuleGroup, function ( rawRule ) {
						const rule = new AW.Rule( group );

						rule.set( 'name', rawRule.name );
						rule.resetOptions();
						rule.set( 'compare', rawRule.compare );
						rule.set( 'value', rawRule.value );

						// for objects
						if ( rawRule.selected ) {
							rule.set( 'selected', rawRule.selected );
						}

						rules.push( rule );
					} );

					group.set( 'rules', rules );
					ruleOptions.push( group );
				} );
			}

			this.set( 'ruleOptions', ruleOptions );

			this.resetAvailableRules();
		},

		defaults() {
			return {
				allRules: {},
				availableRules: {},

				// array of condition group models
				ruleOptions: [],
			};
		},

		resetAvailableRules() {
			// calculate available conditions based on the selected trigger

			const trigger = AW.workflow.get( 'trigger' );

			this.set(
				'availableRules',
				_.filter( this.get( 'allRules' ), function ( rule ) {
					return (
						trigger &&
						trigger.supplied_data_items.indexOf(
							rule.data_item
						) !== -1
					);
				} )
			);

			// put rules into groups for select
			const groupedRules = {};

			_.each( this.get( 'availableRules' ), function ( rule ) {
				if ( ! groupedRules[ rule.group ] ) {
					groupedRules[ rule.group ] = [];
				}
				groupedRules[ rule.group ].push( rule );
			} );

			this.set( 'groupedRules', groupedRules );
		},

		isRuleAvailable( ruleName ) {
			const availableRules = AW.rules.get( 'availableRules' );
			const names = _.pluck( availableRules, 'name' );
			return _.indexOf( names, ruleName ) !== -1;
		},

		clearIncompatibleRules() {
			const rulesToRemove = [];

			_.each( AW.rules.get( 'ruleOptions' ), function ( ruleGroup ) {
				_.each( ruleGroup.get( 'rules' ), function ( rule ) {
					if (
						rule &&
						! AW.rules.isRuleAvailable( rule.get( 'name' ) )
					) {
						rulesToRemove.push( rule );
					}
				} );
			} );

			// clear out of initial loop to avoid index changing issues, when rules are cleared
			_.each( rulesToRemove, function ( rule ) {
				rule.clear();
			} );
		},

		createGroup() {
			const groups = this.get( 'ruleOptions' );

			const group = new AW.RuleGroup( this );
			group.createRule();
			groups.push( group );

			this.set( 'ruleOptions', groups );
			this.trigger( 'ruleGroupChange' );

			return group;
		},

		removeGroup( id ) {
			const groups = this.get( 'ruleOptions' );

			// find index - note we cant use _.findIndex due to backwards compatibility
			const index = groups
				.map( function ( group ) {
					return group.id;
				} )
				.indexOf( id );

			groups[ index ].destroy();
			groups.splice( index, 1 );
			this.set( 'ruleOptions', groups );
			this.trigger( 'ruleGroupChange' );
		},
	} );

	AW.Rule = Backbone.Model.extend( {
		initialize( group ) {
			this.set( 'id', _.uniqueId( 'rule_' ) );
			this.set( 'group', group );

			this.resetOptions();
		},

		getRuleObject() {
			return data.allRules[ this.get( 'name' ) ];
		},

		resetOptions() {
			const name = this.get( 'name' );
			const ruleObject = this.getRuleObject();

			if ( name ) {
				this.set( 'object', ruleObject );
			} else {
				this.set( 'object', {} );
			}

			this.set( 'compare', false );
			this.set( 'value', false );

			this.loadSelectOptions();

			return this;
		},

		/**
		 * async gather rule select choices, if not already loaded
		 */
		loadSelectOptions() {
			const ruleObject = this.getRuleObject();

			if (
				! ruleObject ||
				ruleObject.type !== 'select' ||
				ruleObject.select_choices
			) {
				return this;
			}

			this.set( 'isValueLoading', true );

			$.getJSON(
				ajaxurl,
				{
					action: 'aw_get_rule_select_choices',
					rule_name: ruleObject.name,
				},
				( response ) => {
					if ( ! response.success ) {
						return;
					}

					ruleObject.select_choices = response.data.select_choices;

					this.set( 'isValueLoading', false );
					this.set( 'object', ruleObject );
					this.trigger( 'optionsLoaded' );
				}
			);

			return this;
		},

		clear() {
			const group = this.get( 'group' );
			group.removeRule( this.id );
		},

		destroy() {
			this.trigger( 'destroy' );
		},
	} );

	AW.RuleGroup = Backbone.Model.extend( {
		initialize( app ) {
			this.set( 'id', _.uniqueId( 'rule_group_' ) );
			this.set( 'app', app );
			this.set( 'rules', [] );
		},

		createRule() {
			const rules = this.get( 'rules' );
			const rule = new AW.Rule( this );
			rules.push( rule );
			this.set( 'rules', rules );
			return rule;
		},

		removeRule( id ) {
			const rules = this.get( 'rules' );

			// find rule index - note we cant use _.findIndex due to backwards compatibility
			const index = rules
				.map( function ( rule ) {
					return rule.id;
				} )
				.indexOf( id );

			// if only 1 rule left delete the whole group object
			if ( rules.length > 1 ) {
				rules[ index ].destroy();
				rules.splice( index, 1 );
				this.set( 'rules', rules );
			} else {
				rules[ index ].destroy(); // destroy the last rule
				this.clear();
			}
		},

		clear() {
			const app = this.get( 'app' );
			app.removeGroup( this.id );
		},

		destroy() {
			this.trigger( 'destroy' );
		},
	} );

	// VIEWS

	AW.RuleView = Backbone.View.extend( {
		className: 'automatewoo-rule-container',

		template: wp.template( 'aw-rule' ),

		events: {
			'change .js-rule-select': 'updatedName',
			'change .js-rule-compare-field': 'updatedCompare',
			'change .js-rule-value-field': 'updatedValue',
			'click .js-remove-rule': 'clear',
			'change .js-rule-value-from': 'updateMinFromValueDate',
		},

		initialize() {
			this.listenTo( this.model, 'change:id', this.render );
			this.listenTo( this.model, 'change:group', this.render );
			this.listenTo( this.model, 'optionsLoaded', this.render );
			this.listenTo( this.model, 'destroy', this.remove );
		},

		render() {
			this.$el.html(
				this.template( {
					rule: this.model.toJSON(),
					groupedRules: AW.rules.get( 'groupedRules' ),
					fieldNameBase: this.getFieldNameBase(),
				} )
			);

			this.setName();
			this.setCompare();
			this.setValue();
			this.maybeToggleValueDisplay();
			this.initDatepicker();

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return this;
		},

		setName() {
			this.$el.find( '.js-rule-select' ).val( this.model.get( 'name' ) );
		},

		setCompare() {
			const $compareField = this.$el.find( '.js-rule-compare-field' );
			const compare = this.model.get( 'compare' );

			// Default selected value to first option.
			if ( $compareField.filter( 'select' ).length && ! compare ) {
				const $option = $compareField.find( 'option:first-child' );
				const optionValue = $compareField
					.find( 'option:first-child' )
					.prop( 'value' );

				$option.prop( 'selected', true );
				$compareField.val( optionValue );
				this.model.set( 'compare', optionValue );
			}

			if ( compare ) {
				$compareField.val( compare );
				$compareField
					.find( 'option[value~="' + compare + '"]' )
					.prop( 'selected', true );
			}
		},

		setValue() {
			const selectedTitle = this.model.get( 'selected' );
			const selectedId = this.model.get( 'value' );
			let $valueField;

			if ( selectedTitle ) {
				$valueField = this.$el.find( '.js-rule-value-field' );

				if ( $valueField.is( 'select' ) ) {
					if ( _.isArray( selectedId ) ) {
						_.each( selectedId, function ( id, i ) {
							$valueField.append(
								$( '<option>', {
									value: id,
									text: selectedTitle[ i ],
								} )
							);
						} );
					} else {
						$valueField.append(
							$( '<option>', {
								value: selectedId,
								text: selectedTitle,
							} )
						);
					}
				} else {
					// wc 3.0
					$valueField.attr( 'data-selected', selectedTitle );
				}
			}

			if ( selectedId ) {
				const $fields = this.$el.find( '.js-rule-value-field' );

				if ( this.hasMultipleValueFields() ) {
					if ( _.isArray( selectedId ) ) {
						$fields.each( function ( i, el ) {
							$( el ).val( selectedId[ i ] );
						} );
					}

					if ( _.isObject( selectedId ) ) {
						Object.keys( selectedId ).forEach( ( key ) => {
							$( '.js-rule-value-' + key, this.$el ).val(
								selectedId[ key ]
							);
						} );
					}
				} else {
					$fields.val( selectedId );
				}
			}
		},

		updatedName( e ) {
			this.model.set( 'name', e.target.value ).resetOptions();
			this.render();
		},

		updatedCompare( e ) {
			this.model.set( 'compare', e.target.value );
			this.render();
		},

		/**
		 * There can be more than one value field.
		 *
		 * @param {Event} event
		 */
		updatedValue( event ) {
			let value;

			if ( this.hasMultipleValueFields() ) {
				value = [];

				this.$el.find( '.js-rule-value-field' ).each( function () {
					value.push( $( this ).val() );
				} );
			} else {
				value = event.target.value;
			}

			this.model.set( 'value', value );
		},

		getFieldNameBase() {
			const id = this.model.get( 'id' );
			const group = this.model.get( 'group' );
			return (
				'aw_workflow_data[rule_options][' + group.id + '][' + id + ']'
			);
		},

		clear() {
			this.model.clear();
		},

		/**
		 * @return {boolean|undefined} Whether the model object `has_multiple_value_fields`.
		 */
		hasMultipleValueFields() {
			const object = this.model.get( 'object' );
			return object && object.has_multiple_value_fields;
		},

		maybeToggleValueDisplay() {
			const compare = this.model.get( 'compare' );
			const $valueFields = this.$el.find( '[data-aw-compare]' );

			if ( $valueFields.length ) {
				// Hide value fields.
				$valueFields
					.addClass( 'aw-hidden' )
					.prop( 'required', false )
					.find( 'select, input' )
					.prop( 'required', false );

				// Show our selected rules.
				$valueFields
					.filter( '[data-aw-compare~="' + compare + '"]' )
					.removeClass( 'aw-hidden' )
					.prop( 'required', true )
					.find( 'select, input' )
					.prop( 'required', true );
			}
		},

		initDatepicker() {
			this.$el.find( '.js-date-picker' ).datepicker( {
				dateFormat: 'yy-mm-dd',
				showButtonPanel: true,
			} );
		},

		updateMinFromValueDate() {
			const $from = this.$el.find( '.js-rule-value-from' );
			const $to = this.$el.find( '.js-rule-value-to' );

			if ( $from.length && $to.length ) {
				$to.datepicker( 'option', 'minDate', $from.val() );
			}
		},
	} );

	AW.RuleGroupView = Backbone.View.extend( {
		className: 'aw-rule-group',

		template: wp.template( 'aw-rule-group' ),

		events: {
			'click .js-add-rule': 'addRule',
		},

		initialize() {
			this.listenTo( this.model, 'refreshRules', this.refreshRules );
			this.listenTo( this.model, 'change:id', this.refreshRules );
			this.listenTo( this.model, 'destroy', this.remove );
		},

		render() {
			if ( this.model.get( 'rules' ).length ) {
				this.$el.html( this.template( this.model.toJSON() ) );

				this.$el.find( '.rules' ).empty();

				_.each( this.model.get( 'rules' ), ( rule ) => {
					const view = new AW.RuleView( { model: rule } );
					this.$el.find( '.rules' ).append( view.render().el );
				} );
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return this;
		},

		addRule() {
			const model = this.model.createRule();
			const view = new AW.RuleView( { model } );

			this.$el.find( '.rules' ).append( view.render().el );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return this;
		},

		refreshRules() {
			_.each( this.model.get( 'rules' ), function ( rule ) {
				rule.trigger( 'change:group' );
			} );
		},

		clear() {
			this.undelegateEvents();
			this.model.clear();
		},
	} );

	AW.RulesView = Backbone.View.extend( {
		/**
		 * Element
		 */
		el: $( '#aw-rules-container' ),

		$meta_box: $( '#aw_rules_box' ),

		template: wp.template( 'aw-rules-container' ),

		events: {
			'click .js-add-rule-group': 'addGroup',
		},

		initialize() {
			this.listenTo(
				this.model,
				'ruleGroupChange',
				this.maybeShowEmptyMessage
			);
			this.listenTo(
				this.model,
				'change:groupedRules',
				this.refreshRules
			);

			this.render();
		},

		render() {
			const trigger = AW.workflow.get( 'trigger' );

			this.$el.html(
				this.template( {
					app: this,
					trigger,
				} )
			);

			const $groups = this.$el.find( '.aw-rule-groups' );
			const groups = this.model.get( 'ruleOptions' );

			if ( groups.length ) {
				_.each( groups, function ( group ) {
					const view = new AW.RuleGroupView( { model: group } );
					$groups.append( view.render().el );
				} );
			} else {
				this.addEmptyMessage();
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return this;
		},

		addGroup() {
			const model = this.model.createGroup();
			const view = new AW.RuleGroupView( { model } );

			this.$el.find( '.aw-rule-groups' ).append( view.render().el );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			return this;
		},

		maybeShowEmptyMessage() {
			if ( this.model.get( 'ruleOptions' ).length ) {
				this.removeEmptyMessage();
			} else {
				this.addEmptyMessage();
			}
		},

		addEmptyMessage() {
			this.$el
				.find( '.aw-rule-groups' )
				.html( wp.template( 'aw-rule-groups-empty' ) );
		},

		removeEmptyMessage() {
			this.$el.find( '.aw-rules-empty-message' ).remove();
		},

		refreshRules() {
			_.each( this.model.get( 'ruleOptions' ), function ( group ) {
				group.trigger( 'refreshRules' );
			} );
		},
	} );

	$( function () {
		AW.rules = new AW.Rules( {
			allRules: data.allRules,
			rawRuleOptions: data.ruleOptions,
		} );

		AW.rulesView = new AW.RulesView( {
			model: AW.rules,
		} );
	} );
} )( jQuery, automatewooWorkflowLocalizeScript );

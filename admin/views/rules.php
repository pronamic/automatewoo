<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var AutomateWoo\Workflow $workflow
 */

?>


<div id="aw-rules-container"></div>



<script type="text/template" id="tmpl-aw-rules-container">

	<div class="aw-rules-container">
		<div class="aw-rule-groups"></div>
	</div>

	<div class="automatewoo-metabox-footer">
		<button type="button" class="js-add-rule-group button button-primary button-large"><?php esc_attr_e( '+ Add Rule Group', 'automatewoo' ); ?></button>
	</div>

</script>



<script type="text/template" id="tmpl-aw-rule-groups-empty">
	<p class="aw-rules-empty-message"><?php /* translators: %1$s opening strong tag, %2$s closing strong tag */ printf( esc_attr__( 'Rules can be used to add conditional logic to workflows. Click the %1$s+ Add Rule Group%2$s button to create a rule.', 'automatewoo' ), '<strong>', '</strong>' ); ?></p>
</script>


<script type="text/template" id="tmpl-aw-rule">

	<?php // data.rule.object can be false if the rule was part of an integration that has been removed ?>
	<# if ( data.rule.object ) { #>

		<div class="automatewoo-rule automatewoo-rule--type-{{ data.rule.object.type ? data.rule.object.type : 'new' }} automatewoo-rule--compare-{{ data.rule.compare }}">

			<div class="automatewoo-rule__fields">

				<div class="aw-rule-select-container automatewoo-rule__field-container">
					<select name="{{ data.fieldNameBase }}[name]" class="js-rule-select automatewoo-field" required>

						<option value=""><?php esc_attr_e( '[Select Rule]', 'automatewoo' ); ?></option>
						<# _.each( data.groupedRules, function( rules, group_name ) { #>
							<optgroup label="{{ group_name }}">
								<# _.each( rules, function( rule ) { #>
									<option value="{{ rule.name }}">{{ rule.title }}</option>
								<# }) #>
							</optgroup>
						<# }) #>
					</select>
				</div>

				<div class="aw-rule-field-compare automatewoo-rule__field-container">
					<select name="{{ data.fieldNameBase }}[compare]" class="automatewoo-field js-rule-compare-field" <# if ( _.isEmpty( data.rule.object.compare_types ) ) { #>disabled<# } #>>
						<# _.each( data.rule.object.compare_types, function( option, key ) { #>
							<option value="{{ key }}">{{ option }}</option>
						<# }) #>
					</select>
				</div>


				<div class="aw-rule-field-value automatewoo-rule__field-container <# if ( data.rule.isValueLoading ) { #>aw-loading<# } #>">

					<# if ( data.rule.isValueLoading ) { #>

						<div class="aw-loader"></div>

					<# } else { #>


						<# if ( data.rule.object.type === 'number' ) { #>

							<# if ( data.rule.compare === 'multiple_with_offset' || data.rule.compare === 'not_multiple_with_offset' ) { #>
								<div class="field-cols">
									<div class="col-1">
										<input name="{{ data.fieldNameBase }}[value][multiple]" class="automatewoo-field js-rule-value-field js-rule-value-multiple" type="number" step="1" required placeholder="<?php esc_attr_e( 'multiple of', 'automatewoo' ); ?>">
									</div>
									<div class="col-2">
										<input name="{{ data.fieldNameBase }}[value][offset]" class="automatewoo-field js-rule-value-field js-rule-value-offset" type="number" step="1" required placeholder="<?php esc_attr_e( 'starting at', 'automatewoo' ); ?>">
									</div>
								</div>
							<# } else if ( data.rule.object.has_payment_count_scope === true ) { #>
								<div class="field-cols">
									<div class="col-1">
										<input name="{{ data.fieldNameBase }}[value][count]" class="automatewoo-field js-rule-value-field js-rule-value-count" type="text" required>
									</div>
									<div class="col-2">
										<select name="{{ data.fieldNameBase }}[value][count_scope]" class="automatewoo-field js-rule-value-field js-rule-value-count_scope">
											<option value="current"><?php esc_html_e( 'Current subscription', 'automatewoo' ); ?></option>
											<option value="include_resubscriptions"><?php esc_html_e( 'Current and previous resubscriptions', 'automatewoo' ); ?></option>
										</select>
									</div>
								</div>
							<# } else { #>
								<input name="{{ data.fieldNameBase }}[value]" class="automatewoo-field js-rule-value-field" type="text" required>
							<# } #>

						<# } else if ( data.rule.object.type === 'object' ) { #>

							<select name="{{ data.fieldNameBase }}[value]{{ data.rule.object.is_multi ? '[]' : '' }}"
								class="{{ data.rule.object.class }} automatewoo-field js-rule-value-field"
								data-placeholder="{{ data.rule.object.placeholder }}"
								data-action="{{ data.rule.object.ajax_action }}"
								{{ data.rule.object.is_multi ? 'multiple="multiple"' : '' }}
							></select>

						<# } else if ( data.rule.object.type === 'select' ) { #>

							<# if ( data.rule.object.is_single_select ) { #>
								<select name="{{ data.fieldNameBase }}[value]" class="automatewoo-field wc-enhanced-select js-rule-value-field" data-placeholder="{{{ data.rule.object.placeholder }}}">
									<# if ( data.rule.object.placeholder ) { #>
										<option></option>
									<# } #>
							<# } else { #>
								<select name="{{ data.fieldNameBase }}[value][]" multiple="multiple" class="automatewoo-field wc-enhanced-select js-rule-value-field">
							<# } #>

								<# _.each( data.rule.object.select_choices, function( option, key ) { #>
									<option value="{{ key }}">{{{ option }}}</option>
								<# }) #>

							</select>

						<# } else if ( data.rule.object.type === 'string' && ( data.rule.compare != 'blank' && data.rule.compare != 'not_blank' ) )  { #>

								<input name="{{ data.fieldNameBase }}[value]" class="automatewoo-field js-rule-value-field" type="text" required>

						<# } else if ( data.rule.object.type === 'meta' )  { #>

							<input
								name="{{ data.fieldNameBase }}[value][]"
								class="automatewoo-field js-rule-value-field js-rule-value-field--meta-key"
								type="text"
								placeholder="<?php esc_attr_e( 'key', 'automatewoo' ); ?>"
								<# if ( ! _.isEmpty( data.rule.object.internal_meta_keys ) ) { #>
									data-aw-internal-meta-keys="{{ data.rule.object.internal_meta_keys.join( ' ' ) }}"
								<# } #>
							>
							<input name="{{ data.fieldNameBase }}[value][]" class="automatewoo-field js-rule-value-field" type="text" placeholder="<?php esc_attr_e( 'value', 'automatewoo' ); ?>">
							<# if ( data.rule.object.internal_meta_key_warning ) { #>
								<p class="aw-field-description aw-internal-meta-key-warning aw-hidden">{{ data.rule.object.internal_meta_key_warning }}</p>
							<# } #>

						<# } else if ( data.rule.object.type === 'bool' )  { #>

							<select name="{{ data.fieldNameBase }}[value]" class="automatewoo-field js-rule-value-field">
									<# _.each( data.rule.object.select_choices, function( option, key ) { #>
									<option value="{{ key }}">{{{ option }}}</option>
									<# }); #>
							</select>

							<# } else if ( data.rule.object.type === 'date' ) { #>
								<# if ( data.rule.object.uses_datepicker === true ) { #>
									<# if ( data.rule.object.has_time_of_day === true ) { #>
										<div class="field-cols aw-hidden" data-aw-compare="is_after is_before is_on is_not_on">
											<div class="col-1">
												<input type="text" name="{{ data.fieldNameBase }}[value][date]" class="automatewoo-field js-rule-value-field js-rule-value-date js-date-picker date-picker" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" autocomplete="off"/>
											</div>
											<div class="col-2">
												<input type="time" step="60" name="{{ data.fieldNameBase }}[value][time]" class="automatewoo-field js-rule-value-field js-rule-value-time" data-aw-optional="true" aria-label="<?php esc_attr_e( 'Time', 'automatewoo' ); ?>" autocomplete="off"/>
											</div>
										</div>
									<# } else { #>
										<input type="text" name="{{ data.fieldNameBase }}[value][date]" class="automatewoo-field js-rule-value-field js-rule-value-date js-date-picker date-picker aw-hidden" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" data-aw-compare="is_after is_before is_on is_not_on" autocomplete="off"/>
									<# } #>
								<# } #>
								<# if ( data.rule.object.has_is_between_dates === true ) { #>
									<div class="field-cols aw-hidden" data-aw-compare="is_between">
										<div class="col-1">
											<input type="text" name="{{ data.fieldNameBase }}[value][from]" class="automatewoo-field js-rule-value-field js-rule-value-from date-picker js-date-picker" placeholder="start" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" autocomplete="off"/>
										</div>
										<div class="col-2">
											<input type="text" name="{{ data.fieldNameBase }}[value][to]" class="automatewoo-field js-rule-value-field js-rule-value-to date-picker js-date-picker" placeholder="end" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" autocomplete="off"/>
										</div>
									</div>
							<# } #>
							<# if ( data.rule.object.has_days_of_the_week === true ) { #>
									<div class="aw-hidden" data-aw-compare="days_of_the_week">
										<select name="{{ data.fieldNameBase }}[value][dow][]" multiple required class="automatewoo-field js-rule-value-field js-rule-value-dow wc-enhanced-select">
											<?php for ( $day = 1; $day <= 7; $day++ ) : ?>
												<option value="<?php echo esc_attr( $day ); ?>"><?php echo esc_attr( AutomateWoo\Format::weekday( $day ) ); ?></option>
											<?php endfor; ?>
										</select>
									</div>
							<# } #>
							<# if( data.rule.object.has_is_future_comparision === true || data.rule.object.has_is_past_comparision === true ) { #>
									<div class="field-cols aw-hidden" data-aw-compare="is_in_the_next is_not_in_the_next is_in_the_last is_not_in_the_last">
										<div class="col-1">
											<input type="number" step="1" min="1" name="{{ data.fieldNameBase }}[value][timeframe]" class="automatewoo-field js-rule-value-field js-rule-value-timeframe" required/>
										</div>
										<div class="col-2">
											<select name="{{ data.fieldNameBase }}[value][measure]" class="automatewoo-field js-rule-value-field js-rule-value-measure" required>
												<# _.each( data.rule.object.select_choices, function( option, key ) { #>
												<option value="{{ key }}">{{{ option }}}</option>
												<# }); #>
											</select>
										</div>
									</div>
							<# } #>

						<# } else { #>

							<input class="automatewoo-field" type="text" disabled>

						<# } #>


					<# } #>


				</div>

			</div>

			<div class="automatewoo-rule__buttons">
				<button type="button" class="js-add-rule automatewoo-rule__add button"><?php esc_html_e( 'and', 'automatewoo' ); ?></button>
				<button type="button" class="js-remove-rule automatewoo-rule__remove"></button>
			</div>

		</div>

	<# } else { #>

		<div class="automatewoo-missing-rule">
			<?php /* translators: Rule name. */ echo wp_kses_post( sprintf( esc_html__( 'This rule %1$s is no longer available and will be removed by saving this workflow.', 'automatewoo' ), '({{ data.rule.name }})' ) ); ?>
		</div>

	<# } #>


</script>

<script type="text/template" id="tmpl-aw-rule-group">
	<div class="rules"></div>
	<div class="aw-rule-group__or"><span><?php esc_attr_e( 'or', 'automatewoo' ); ?></span></div>
</script>

<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Workflow $workflow
 */

$option_base = 'aw_workflow_data[workflow_options]';

// When to Run field.
$when_to_run = new Fields\Select( false );
$when_to_run
	->set_name_base( $option_base )
	->set_name( 'when_to_run' )
	->set_options(
		apply_filters(
			'automatewoo/workflow/timing_options',
			[
				'immediately' => __( 'Run immediately', 'automatewoo' ),
				'delayed'     => __( 'Delayed', 'automatewoo' ),
				'scheduled'   => __( 'Scheduled', 'automatewoo' ),
				'fixed'       => __( 'Fixed', 'automatewoo' ),
				'datetime'    => __( 'Schedule with a variable', 'automatewoo' ),
			]
		)
	)
	->add_data_attr( 'automatewoo-bind', 'timing' );

// Queue date time field.
$queue_datetime = new Fields\Text_Area();
$queue_datetime
	->set_rows( 3 )
	->set_name_base( $option_base )
	->set_name( 'queue_datetime' )
	->add_classes( 'automatewoo-field--monospace' )
	->add_extra_attr( 'spellcheck', 'false' )
	->set_placeholder( __( "e.g. {{ subscription.next_payment_date | modify: '-1 day' }}", 'automatewoo' ) );

// Scheduled time field.
$options         = [];
$minute_interval = 15;
for ( $hours = 0; $hours < 24; $hours++ ) {
	for ( $min = 0; $min < 60 / $minute_interval; $min++ ) {
		$options[] = zeroise( $hours, 2 ) . ':' . zeroise( $min * $minute_interval, 2 );
	}
}

$scheduled_time = new Fields\Select( false );
$scheduled_time
	->set_name_base( $option_base )
	->set_default( '09:00' )
	->set_name( 'scheduled_time' )
	->set_options( array_combine( $options, $options ) );

// Scheduled Day field.
$options = [];
for ( $day = 1; $day <= 7; $day++ ) {
	$options[ $day ] = Format::weekday( $day );
}

$scheduled_day = new Fields\Select( false );
$scheduled_day
	->set_name_base( $option_base )
	->set_name( 'scheduled_day' )
	->set_placeholder( __( '[Any day]', 'automatewoo' ) )
	->set_multiple()
	->set_options( $options );

// Run delay fields.
$run_delay_value = new Fields\Number();
$run_delay_value
	->set_name_base( $option_base )
	->set_name( 'run_delay_value' )
	->set_min( '0' )
	->add_extra_attr( 'step', 'any' );

$run_delay_unit = new Fields\Select( false );
$run_delay_unit
	->set_name_base( $option_base )
	->set_name( 'run_delay_unit' )
	->set_options(
		[
			'h'     => __( 'Hours', 'automatewoo' ),
			'm'     => __( 'Minutes', 'automatewoo' ),
			'd'     => __( 'Days', 'automatewoo' ),
			'w'     => __( 'Weeks', 'automatewoo' ),
			'month' => __( 'Months', 'automatewoo' ),
		]
	);

// Date/time fields.
$fixed_date = new Fields\Date();
$fixed_date
	->set_name_base( $option_base )
	->set_name( 'fixed_date' );

$fixed_time = new Fields\Time();
$fixed_time->set_name_base( $option_base )
	->set_name( 'fixed_time' )
	->set_show_24hr_note( false );

?>
<table class="automatewoo-table">
	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php
				esc_html_e( 'Timing', 'automatewoo' );
				echo Admin::help_link( Admin::get_docs_link( 'timing', 'workflow-edit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</label>

			<?php $when_to_run->render( $workflow ? $workflow->get_timing_type() : '' ); ?>
		</td>
	</tr>

	<tr class="automatewoo-table__row" data-automatewoo-show="timing=datetime">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label"><?php esc_html_e( 'Variable', 'automatewoo' ); ?></label>
			<?php $queue_datetime->render( $workflow ? $workflow->get_option( 'queue_datetime' ) : '' ); ?>
		</td>
	</tr>

	<tr class="automatewoo-table__row" data-automatewoo-show="timing=scheduled">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php esc_html_e( 'Scheduled time', 'automatewoo' ); ?>
				<span class="automatewoo-label__extra"><?php esc_html_e( '(24hr)', 'automatewoo' ); ?></span>
			</label>

			<?php $scheduled_time->render( $workflow ? $workflow->get_scheduled_time() : '' ); ?>
		</td>
	</tr>

	<tr class="automatewoo-table__row" data-automatewoo-show="timing=scheduled">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php esc_html_e( 'Scheduled days', 'automatewoo' ); ?>
				<span class="automatewoo-label__extra"><?php esc_html_e( '(optional)', 'automatewoo' ); ?></span>
			</label>

			<?php $scheduled_day->render( $workflow ? $workflow->get_scheduled_days() : '' ); ?>
		</td>
	</tr>

	<tr class="automatewoo-table__row" data-automatewoo-show="timing=delayed|scheduled">
		<td class="automatewoo-table__col">
			<div class="field-cols">
				<div class="automatewoo-label" data-automatewoo-show="timing=delayed">
					<?php esc_html_e( 'Length of the delay', 'automatewoo' ); ?>
				</div>

				<div class="automatewoo-label" data-automatewoo-show="timing=scheduled">
					<?php esc_html_e( 'Minimum wait', 'automatewoo' ); ?>
					<span class="automatewoo-label__extra"><?php esc_html_e( '(optional)', 'automatewoo' ); ?></span>
				</div>

				<div class="col-1">
					<?php $run_delay_value->render( $workflow ? $workflow->get_option( 'run_delay_value' ) : '' ); ?>
				</div>

				<div class="col-2">
					<?php $run_delay_unit->render( $workflow ? $workflow->get_option( 'run_delay_unit' ) : '' ); ?>
				</div>
			</div>
		</td>
	</tr>

	<tr class="automatewoo-table__row" data-automatewoo-show="timing=fixed">
		<td class="automatewoo-table__col">
			<div class="field-cols">
				<label class="automatewoo-label">
					<?php esc_html_e( 'Date', 'automatewoo' ); ?>
					<span class="automatewoo-label__extra"><?php esc_html_e( '(24 hour time)', 'automatewoo' ); ?></span>
				</label>
				<div class="col-1">
					<?php $fixed_date->render( $workflow ? $workflow->get_option( 'fixed_date' ) : gmdate( 'Y-m-d' ) ); ?>
				</div>

				<div class="col-2">
					<?php
					if ( $workflow && $workflow->get_option( 'fixed_time' ) ) {
						$value = Clean::recursive( (array) $workflow->get_option( 'fixed_time' ) );
					} else {
						$value = [ gmdate( 'H' ), gmdate( 'i' ) ];
					}

					$fixed_time->render( $value );
					?>
				</div>
			</div>
		</td>
	</tr>
</table>

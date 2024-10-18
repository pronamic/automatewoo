<?php

namespace AutomateWoo\Workflows;

use AutomateWoo\Entity\WorkflowTimingDelayed;
use AutomateWoo\Entity\WorkflowTimingFixed;
use AutomateWoo\Entity\WorkflowTimingImmediate;
use AutomateWoo\Entity\WorkflowTimingScheduled;
use AutomateWoo\Entity\WorkflowTimingVariable;
use AutomateWoo\Exceptions\InvalidValue;
use AutomateWoo\Format;
use AutomateWoo\Trigger;
use AutomateWoo\Workflow;

/**
 * Class TimingDescriptionGenerator
 *
 * @since 5.4.0
 */
class TimingDescriptionGenerator {

	/**
	 * @var Workflow
	 */
	protected $workflow;

	/**
	 * @var Trigger
	 */
	protected $trigger;

	/**
	 * TimingDescriptionGenerator constructor.
	 *
	 * @param Workflow $workflow
	 *
	 * @throws InvalidValue If workflow has no trigger.
	 */
	public function __construct( Workflow $workflow ) {
		$this->workflow = $workflow;
		$this->trigger  = $workflow->get_trigger();

		if ( ! $this->trigger ) {
			throw InvalidValue::item_not_found( 'workflow trigger' );
		}
	}

	/**
	 * Generate the timing description string.
	 *
	 * @return string
	 */
	public function generate(): string {
		if ( ! $this->trigger::SUPPORTS_QUEUING ) {
			return _x( 'Custom.', 'timing option', 'automatewoo' );
		}
		$text_parts = [];

		switch ( $this->workflow->get_timing_type() ) {
			case WorkflowTimingImmediate::TYPE:
				// Hide this "Immediate" text for custom time of day triggers
				if ( ! $this->trigger::SUPPORTS_CUSTOM_TIME_OF_DAY ) {
					$text_parts[] = _x( 'Immediate', 'timing option', 'automatewoo' );
				}
				break;
			case WorkflowTimingDelayed::TYPE:
				$text_parts[] = $this->get_delayed_timing_text();
				break;
			case WorkflowTimingScheduled::TYPE:
				$text_parts[] = $this->get_scheduled_timing_text();
				break;
			case WorkflowTimingFixed::TYPE:
				$text_parts[] = $this->get_fixed_timing_text();
				break;
			case WorkflowTimingVariable::TYPE:
				$text_parts[] = __( 'Scheduled with a variable', 'automatewoo' );
				break;
		}

		// Prepend time of day text to start of description
		if ( $this->trigger::SUPPORTS_CUSTOM_TIME_OF_DAY ) {
			array_unshift( $text_parts, $this->get_time_of_day_text() );
		}

		return implode( '. ', array_filter( $text_parts ) ) . '.';
	}

	/**
	 * Get text for delayed workflow.
	 *
	 * @return string
	 */
	protected function get_delayed_timing_text(): string {
		/* translators: Delayed amount in text. */
		return sprintf( _x( 'Delayed for <b>%s</b>', 'timing option', 'automatewoo' ), $this->get_delay_amount_text() );
	}

	/**
	 * Get text for scheduled workflow.
	 *
	 * @return string
	 */
	protected function get_scheduled_timing_text(): string {
		$days = $this->workflow->get_scheduled_days();

		$schedule_time = sprintf(
			/* translators: Scheduled time of day. */
			_x( 'Scheduled for <b>%s</b>', 'timing option', 'automatewoo' ),
			Format::time_of_day( $this->workflow->get_scheduled_time() )
		);
		$schedule_days  = '';
		$schedule_delay = '';

		if ( $days ) {
			$schedule_days = sprintf(
				/* translators: Weekday. */
				_x( ' on <b>%s</b>', 'timing option', 'automatewoo' ),
				$this->get_weekday_text( $days )
			);
		}

		if ( $this->workflow->get_timing_delay() ) {
			$schedule_delay = sprintf(
				/* translators: Delayed amount in text. */
				_x( ' after waiting <b>%s</b>', 'timing option', 'automatewoo' ),
				$this->get_delay_amount_text()
			);
		}

		return sprintf( '%s%s%s', $schedule_time, $schedule_days, $schedule_delay );
	}

	/**
	 * Get text for fixed timing workflow.
	 *
	 * @return string
	 */
	protected function get_fixed_timing_text(): string {
		$date = $this->workflow->get_fixed_time();
		if ( $date ) {
			/* translators: Date and time. */
			return sprintf( _x( 'Fixed at %s', 'timing option', 'automatewoo' ), Format::datetime( $date ) );
		}

		return __( 'Invalid time', 'automatewoo' );
	}

	/**
	 * Get text for the workflow's delay amount.
	 *
	 * @return string
	 */
	protected function get_delay_amount_text(): string {
		$unit   = $this->workflow->get_timing_delay_unit();
		$number = $this->workflow->get_timing_delay_number();

		switch ( $unit ) {
			case 'h':
				/* translators: Number of hours. */
				$unit_text = _n( '%s hour', '%s hours', $number, 'automatewoo' );
				break;
			case 'm':
				/* translators: Number of minutes. */
				$unit_text = _n( '%s minute', '%s minutes', $number, 'automatewoo' );
				break;
			case 'd':
				/* translators: Number of days. */
				$unit_text = _n( '%s day', '%s days', $number, 'automatewoo' );
				break;
			case 'w':
				/* translators: Number of weeks. */
				$unit_text = _n( '%s week', '%s weeks', $number, 'automatewoo' );
				break;
			case 'month':
				/* translators: Number of months. */
				$unit_text = _n( '%s month', '%s months', $number, 'automatewoo' );
				break;
			default:
				return '';
		}

		return sprintf( $unit_text, $number );
	}

	/**
	 * @param array $days
	 *
	 * @return string
	 */
	protected function get_weekday_text( array $days ): string {
		$string = '';

		if ( array_diff( $days, [ 1, 2, 3, 4, 5 ] ) === [] && count( $days ) === 5 ) {
			$string .= __( 'Weekdays', 'automatewoo' );
		} elseif ( array_diff( $days, [ 6, 7 ] ) === [] && count( $days ) === 2 ) {
			$string .= __( 'Weekends', 'automatewoo' );
		} else {
			$names = array_map( [ 'AutomateWoo\Format', 'weekday' ], $days );

			if ( count( $names ) > 1 ) {
				$last    = array_pop( $names );
				$string .= implode( ', ', $names );
				$string .= _x( ' or ', 'day', 'automatewoo' ) . $last;
			} else {
				$string .= current( $names );
			}
		}

		return $string;
	}

	/**
	 * Get the text for time of day workflows.
	 *
	 * This is prepended to the description.
	 *
	 * @return string
	 */
	protected function get_time_of_day_text(): string {
		$time = $this->workflow->get_trigger_option( 'time' );
		if ( ! $time ) {
			// Default time of day is 00:00
			$time = [ 0, 0 ];
		}

		$time_string = Format::time_of_day( $time );

		if ( $this->workflow->get_timing_type() === WorkflowTimingImmediate::TYPE ) {
			/* translators: Time of day. */
			$text = _x( 'Runs daily at %s', 'timing option', 'automatewoo' );
		} else {
			/* translators: Time of day. */
			$text = _x( 'Checked daily at %s', 'timing option', 'automatewoo' );
		}

		return sprintf( $text, '<b>' . $time_string . '</b>' );
	}
}

<?php
/**
 * Get AutomateWoo workflow health ability.
 *
 * @package AutomateWoo\Abilities
 */

namespace AutomateWoo\Abilities;

use AutomateWoo\DateTime;
use AutomateWoo\Format;
use AutomateWoo\Log_Query;
use AutomateWoo\Permissions;
use AutomateWoo\Queue_Query;
use AutomateWoo\Workflows\Factory;
use Automattic\WooCommerce\Abilities\AbilityDefinition;
use WP_Error;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( AbilityDefinition::class ) ) {
	return;
}

/**
 * Reads sanitized operational health for an AutomateWoo workflow.
 */
class Get_Workflow_Health implements AbilityDefinition {

	/**
	 * Gets the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'automatewoo/get-workflow-health';
	}

	/**
	 * Gets the ability registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return [
			'label'               => __( 'Get AutomateWoo workflow health', 'automatewoo' ),
			'description'         => __( 'Read a sanitized operational health summary for one AutomateWoo workflow.', 'automatewoo' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => [ __CLASS__, 'execute' ],
			'permission_callback' => [ __CLASS__, 'can_read_workflows' ],
			'meta'                => Workflow_Formatter::get_readonly_meta(),
		];
	}

	/**
	 * Executes the ability.
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute( array $input ) {
		$workflow_id = isset( $input['id'] ) ? absint( $input['id'] ) : 0;

		if ( ! $workflow_id ) {
			return new WP_Error(
				'automatewoo_invalid_workflow_id',
				__( 'A valid workflow ID is required.', 'automatewoo' )
			);
		}

		$workflow = Factory::get( $workflow_id );

		if ( ! $workflow ) {
			return new WP_Error(
				'automatewoo_workflow_not_found',
				__( 'The requested workflow does not exist.', 'automatewoo' )
			);
		}

		$days        = isset( $input['days'] ) ? absint( $input['days'] ) : 30;
		$days        = min( 365, max( 1, $days ) );
		$date_after  = new DateTime( "-{$days} days" );
		$date_before = new DateTime();
		$runs        = self::get_run_counts( $workflow_id, $date_after, $date_before );
		$queue       = self::get_queue_counts( $workflow_id );

		return [
			'workflow'      => Workflow_Formatter::format_workflow( $workflow ),
			'period'        => [
				'days'            => $days,
				'date_after_gmt'  => self::format_datetime( $date_after ),
				'date_before_gmt' => self::format_datetime( $date_before ),
			],
			'runs'          => $runs,
			'queue'         => $queue,
			'health_status' => self::get_health_status( $workflow->get_status(), $runs, $queue ),
		];
	}

	/**
	 * Checks whether the current user can read workflows.
	 *
	 * @return bool
	 */
	public static function can_read_workflows(): bool {
		return Permissions::can_manage();
	}

	/**
	 * Gets workflow run counts for the requested period.
	 *
	 * @param int      $workflow_id Workflow ID.
	 * @param DateTime $date_after Period start date.
	 * @param DateTime $date_before Period end date.
	 * @return array
	 */
	private static function get_run_counts( int $workflow_id, DateTime $date_after, DateTime $date_before ): array {
		return [
			'total'                       => self::get_log_count( $workflow_id, $date_after, $date_before ),
			'failed'                      => self::get_log_count( $workflow_id, $date_after, $date_before, [ 'has_errors' => 1 ] ),
			'blocked_emails'              => self::get_log_count( $workflow_id, $date_after, $date_before, [ 'has_blocked_emails' => 1 ] ),
			'tracking_enabled'            => self::get_log_count( $workflow_id, $date_after, $date_before, [ 'tracking_enabled' => 1 ] ),
			'conversion_tracking_enabled' => self::get_log_count( $workflow_id, $date_after, $date_before, [ 'conversion_tracking_enabled' => 1 ] ),
			'last_run_gmt'                => self::get_last_run_date( $workflow_id, $date_after, $date_before ),
		];
	}

	/**
	 * Gets a workflow log count for the requested filters.
	 *
	 * @param int      $workflow_id Workflow ID.
	 * @param DateTime $date_after Period start date.
	 * @param DateTime $date_before Period end date.
	 * @param array    $where Additional column filters.
	 * @return int
	 */
	private static function get_log_count( int $workflow_id, DateTime $date_after, DateTime $date_before, array $where = [] ): int {
		$query = ( new Log_Query() )
			->where_workflow( $workflow_id )
			->where_date_between( $date_after, $date_before );

		foreach ( $where as $column => $value ) {
			$query->where( $column, $value );
		}

		return $query->get_count();
	}

	/**
	 * Gets the latest workflow run date for the requested period.
	 *
	 * @param int      $workflow_id Workflow ID.
	 * @param DateTime $date_after Period start date.
	 * @param DateTime $date_before Period end date.
	 * @return string|null
	 */
	private static function get_last_run_date( int $workflow_id, DateTime $date_after, DateTime $date_before ): ?string {
		$query = ( new Log_Query() )
			->where_workflow( $workflow_id )
			->where_date_between( $date_after, $date_before )
			->set_ordering( 'date', 'DESC' )
			->set_limit( 1 );

		$logs = $query->get_results();
		$log  = $logs[0] ?? null;

		return $log ? self::format_datetime( $log->get_date() ) : null;
	}

	/**
	 * Gets workflow queue counts.
	 *
	 * @param int $workflow_id Workflow ID.
	 * @return array
	 */
	private static function get_queue_counts( int $workflow_id ): array {
		$date_now = new DateTime();

		return [
			'pending'      => ( new Queue_Query() )
				->where_workflow( $workflow_id )
				->where_failed( false )
				->get_count(),
			'failed'       => ( new Queue_Query() )
				->where_workflow( $workflow_id )
				->where_failed( true )
				->get_count(),
			'overdue'      => ( new Queue_Query() )
				->where_workflow( $workflow_id )
				->where_failed( false )
				->where_date_due( $date_now, '<' )
				->get_count(),
			'next_due_gmt' => self::get_next_due_date( $workflow_id ),
		];
	}

	/**
	 * Gets the next queued event due date.
	 *
	 * @param int $workflow_id Workflow ID.
	 * @return string|null
	 */
	private static function get_next_due_date( int $workflow_id ): ?string {
		$query = ( new Queue_Query() )
			->where_workflow( $workflow_id )
			->where_failed( false )
			->set_ordering( 'date', 'ASC' )
			->set_limit( 1 );

		$events = $query->get_results();
		$event  = $events[0] ?? null;

		if ( ! $event || ! $event->get_date_due() ) {
			return null;
		}

		return self::format_datetime( $event->get_date_due() );
	}

	/**
	 * Gets the summarized workflow health status.
	 *
	 * @param string $workflow_status Workflow status.
	 * @param array  $runs Run counts.
	 * @param array  $queue Queue counts.
	 * @return string
	 */
	private static function get_health_status( string $workflow_status, array $runs, array $queue ): string {
		if ( $queue['failed'] > 0 || $runs['failed'] > 0 || $runs['blocked_emails'] > 0 ) {
			return 'needs_attention';
		}

		if ( 'disabled' === $workflow_status ) {
			return 'disabled';
		}

		if ( $queue['overdue'] > 0 ) {
			return 'delayed';
		}

		if ( 0 === $runs['total'] && 0 === $queue['pending'] ) {
			return 'idle';
		}

		return 'healthy';
	}

	/**
	 * Gets the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'id'   => [
					'type'        => 'integer',
					'description' => __( 'AutomateWoo workflow ID.', 'automatewoo' ),
					'minimum'     => 1,
				],
				'days' => [
					'type'        => 'integer',
					'description' => __( 'Number of days of workflow logs to summarize.', 'automatewoo' ),
					'default'     => 30,
					'minimum'     => 1,
					'maximum'     => 365,
				],
			],
			'required'             => [ 'id' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Gets the ability output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'workflow'      => Workflow_Formatter::get_workflow_schema(),
				'period'        => self::get_period_schema(),
				'runs'          => self::get_runs_schema(),
				'queue'         => self::get_queue_schema(),
				'health_status' => [
					'type' => 'string',
					'enum' => [ 'healthy', 'idle', 'delayed', 'disabled', 'needs_attention' ],
				],
			],
			'required'             => [ 'workflow', 'period', 'runs', 'queue', 'health_status' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Gets the period output schema.
	 *
	 * @return array
	 */
	private static function get_period_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'days'            => [
					'type' => 'integer',
				],
				'date_after_gmt'  => [
					'type'   => 'string',
					'format' => 'date-time',
				],
				'date_before_gmt' => [
					'type'   => 'string',
					'format' => 'date-time',
				],
			],
			'required'             => [ 'days', 'date_after_gmt', 'date_before_gmt' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Gets the runs output schema.
	 *
	 * @return array
	 */
	private static function get_runs_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'total'                       => [
					'type' => 'integer',
				],
				'failed'                      => [
					'type' => 'integer',
				],
				'blocked_emails'              => [
					'type' => 'integer',
				],
				'tracking_enabled'            => [
					'type' => 'integer',
				],
				'conversion_tracking_enabled' => [
					'type' => 'integer',
				],
				'last_run_gmt'                => [
					'type'   => [ 'string', 'null' ],
					'format' => 'date-time',
				],
			],
			'required'             => [ 'total', 'failed', 'blocked_emails', 'tracking_enabled', 'conversion_tracking_enabled', 'last_run_gmt' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Gets the queue output schema.
	 *
	 * @return array
	 */
	private static function get_queue_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'pending'      => [
					'type' => 'integer',
				],
				'failed'       => [
					'type' => 'integer',
				],
				'overdue'      => [
					'type' => 'integer',
				],
				'next_due_gmt' => [
					'type'   => [ 'string', 'null' ],
					'format' => 'date-time',
				],
			],
			'required'             => [ 'pending', 'failed', 'overdue', 'next_due_gmt' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Formats AutomateWoo date values for ability output.
	 *
	 * @param DateTime|string $datetime Date value.
	 * @return string
	 */
	private static function format_datetime( $datetime ): string {
		if ( ! $datetime instanceof DateTime ) {
			$datetime = new DateTime( (string) $datetime );
		}

		return Format::api_datetime( $datetime );
	}
}

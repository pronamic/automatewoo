<?php
/**
 * List AutomateWoo workflows ability.
 *
 * @package AutomateWoo\Abilities
 */

namespace AutomateWoo\Abilities;

use AutomateWoo\Permissions;
use AutomateWoo\Workflow_Query;
use AutomateWoo\Workflows;
use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( AbilityDefinition::class ) ) {
	return;
}

/**
 * Lists sanitized AutomateWoo workflow summaries.
 */
class List_Workflows implements AbilityDefinition {

	/**
	 * Gets the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'automatewoo/list-workflows';
	}

	/**
	 * Gets the ability registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return [
			'label'               => __( 'List AutomateWoo workflows', 'automatewoo' ),
			'description'         => __( 'List sanitized summaries of AutomateWoo workflows with optional filtering.', 'automatewoo' ),
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
	 * @return array
	 */
	public static function execute( array $input ): array {
		$page     = self::get_page( $input );
		$per_page = self::get_per_page( $input );

		$query = new Workflow_Query();
		$query->set_no_found_rows( false );
		$query->set_page( $page );
		$query->set_limit( $per_page );
		$query->set_status( self::get_status( $input ) );

		if ( ! empty( $input['type'] ) && in_array( $input['type'], array_keys( Workflows::get_types() ), true ) ) {
			$query->set_type( sanitize_key( $input['type'] ) );
		}

		if ( ! empty( $input['search'] ) && is_scalar( $input['search'] ) ) {
			$query->set_search( sanitize_text_field( (string) $input['search'] ) );
		}

		if ( ! empty( $input['trigger'] ) && is_array( $input['trigger'] ) ) {
			$query->set_trigger( array_map( 'sanitize_key', $input['trigger'] ) );
		}

		$workflows = $query->get_results();
		$total     = $query->get_found_rows();

		return [
			'workflows'   => array_map( [ Workflow_Formatter::class, 'format_workflow' ], $workflows ),
			'total'       => $total,
			'total_pages' => $total > 0 ? (int) ceil( $total / $per_page ) : 0,
			'page'        => $page,
			'per_page'    => $per_page,
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
	 * Gets the requested result page.
	 *
	 * @param array $input Ability input.
	 * @return int
	 */
	private static function get_page( array $input ): int {
		$page = isset( $input['page'] ) ? absint( $input['page'] ) : 1;

		return max( 1, $page );
	}

	/**
	 * Gets the requested per-page limit.
	 *
	 * @param array $input Ability input.
	 * @return int
	 */
	private static function get_per_page( array $input ): int {
		$per_page = isset( $input['per_page'] ) ? absint( $input['per_page'] ) : 20;

		return min( 100, max( 1, $per_page ) );
	}

	/**
	 * Gets the requested workflow status filter.
	 *
	 * @param array $input Ability input.
	 * @return string
	 */
	private static function get_status( array $input ): string {
		$status = isset( $input['status'] ) && is_scalar( $input['status'] ) ? sanitize_key( $input['status'] ) : 'any';

		return in_array( $status, [ 'any', 'active', 'disabled' ], true ) ? $status : 'any';
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
				'page'     => [
					'type'        => 'integer',
					'description' => __( 'Current result page.', 'automatewoo' ),
					'default'     => 1,
					'minimum'     => 1,
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => __( 'Maximum number of workflows to return.', 'automatewoo' ),
					'default'     => 20,
					'minimum'     => 1,
					'maximum'     => 100,
				],
				'status'   => [
					'type'        => 'string',
					'description' => __( 'Workflow status to return.', 'automatewoo' ),
					'default'     => 'any',
					'enum'        => [ 'any', 'active', 'disabled' ],
				],
				'type'     => [
					'type'        => 'string',
					'description' => __( 'Workflow type to return.', 'automatewoo' ),
					'enum'        => array_keys( Workflows::get_types() ),
				],
				'search'   => [
					'type'        => 'string',
					'description' => __( 'Search term for workflow titles.', 'automatewoo' ),
				],
				'trigger'  => [
					'type'        => 'array',
					'description' => __( 'Trigger names to return.', 'automatewoo' ),
					'items'       => [
						'type' => 'string',
					],
				],
			],
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
				'workflows'   => [
					'type'  => 'array',
					'items' => Workflow_Formatter::get_workflow_schema(),
				],
				'total'       => [
					'type' => 'integer',
				],
				'total_pages' => [
					'type' => 'integer',
				],
				'page'        => [
					'type' => 'integer',
				],
				'per_page'    => [
					'type' => 'integer',
				],
			],
			'required'             => [ 'workflows', 'total', 'total_pages', 'page', 'per_page' ],
			'additionalProperties' => false,
		];
	}
}

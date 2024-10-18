<?php

namespace AutomateWoo\Entity;

use AutomateWoo\Workflows\Status;

/**
 * @class Workflow
 * @since 5.1.0
 */
class Workflow {

	const TYPE_AUTOMATIC = 'automatic';
	const TYPE_MANUAL    = 'manual';

	const ORIGIN_MANUALLY_CREATED = 'manually_created';

	/**
	 * @var int
	 */
	protected $id = 0;

	/**
	 * @var string
	 */
	protected $type = self::TYPE_AUTOMATIC;

	/**
	 * @var Status
	 */
	protected $status;

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var WorkflowTiming
	 */
	protected $timing;

	/**
	 * @var bool
	 */
	protected $is_transactional = false;

	/**
	 * @var bool
	 */
	protected $is_tracking_enabled = false;

	/**
	 * @var bool
	 */
	protected $is_conversion_tracking_enabled = false;

	/**
	 * Google Analytics link tracking
	 *
	 * This will be appended to every URL in the email content or SMS body. e.g. utm_source=automatewoo&utm_medium=email&utm_campaign=example
	 *
	 * @var string
	 */
	protected $ga_link_tracking = '';

	/**
	 * @var Trigger
	 */
	protected $trigger;

	/**
	 * Multiple groups of rules.
	 *
	 * @var RuleGroup[]
	 */
	protected $rule_groups = [];

	/**
	 * @var Action[]
	 */
	protected $actions = [];

	/**
	 * Order in which the workflows will run.
	 *
	 * @var integer
	 */
	protected $order = 0;

	/**
	 * The origin of the workflow indicating how it was created (e.g. manually, a preset name, etc.)
	 *
	 * @var string
	 */
	protected $origin = self::ORIGIN_MANUALLY_CREATED;

	/**
	 * Workflow constructor.
	 *
	 * @param Trigger             $trigger
	 * @param string              $type
	 * @param WorkflowTiming|null $timing Used when the workflow type is 'automatic'
	 * @param Status|null         $status
	 */
	public function __construct( Trigger $trigger, string $type = self::TYPE_AUTOMATIC, $timing = null, $status = null ) {
		$this->trigger = $trigger;
		$this->type    = $type;
		$this->timing  = $timing ?? new WorkflowTimingImmediate();
		$this->status  = $status ?? new Status( Status::DISABLED );
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function set_id( int $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function set_type( string $type ) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return Status
	 */
	public function get_status(): Status {
		return $this->status;
	}

	/**
	 * @param Status $status
	 * @return $this
	 */
	public function set_status( Status $status ) {
		$this->status = $status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function set_title( string $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * @return WorkflowTiming
	 */
	public function get_timing(): WorkflowTiming {
		return $this->timing;
	}

	/**
	 * @param WorkflowTiming $timing
	 * @return $this
	 */
	public function set_timing( WorkflowTiming $timing ) {
		$this->timing = $timing;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function is_transactional(): bool {
		return $this->is_transactional;
	}

	/**
	 * @param bool $is_transactional
	 * @return $this
	 */
	public function set_is_transactional( $is_transactional ) {
		$this->is_transactional = (bool) $is_transactional;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function is_tracking_enabled(): bool {
		return $this->is_tracking_enabled;
	}

	/**
	 * @param bool $is_tracking_enabled
	 * @return $this
	 */
	public function set_is_tracking_enabled( $is_tracking_enabled ) {
		$this->is_tracking_enabled = (bool) $is_tracking_enabled;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function is_conversion_tracking_enabled(): bool {
		return $this->is_conversion_tracking_enabled;
	}

	/**
	 * @param bool $is_conversion_tracking_enabled
	 * @return $this
	 */
	public function set_is_conversion_tracking_enabled( $is_conversion_tracking_enabled ) {
		$this->is_conversion_tracking_enabled = (bool) $is_conversion_tracking_enabled;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_ga_link_tracking(): string {
		return $this->ga_link_tracking;
	}

	/**
	 * @param string $ga_link_tracking
	 * @return Workflow
	 */
	public function set_ga_link_tracking( string $ga_link_tracking ) {
		$this->ga_link_tracking = $ga_link_tracking;
		return $this;
	}

	/**
	 * @return Trigger
	 */
	public function get_trigger() {
		return $this->trigger;
	}

	/**
	 * @param Trigger $trigger
	 * @return $this
	 */
	public function set_trigger( Trigger $trigger ) {
		$this->trigger = $trigger;
		return $this;
	}

	/**
	 * @return RuleGroup[]
	 */
	public function get_rule_groups() {
		return $this->rule_groups;
	}

	/**
	 * @param RuleGroup[] $rule_groups
	 *
	 * @return $this
	 */
	public function set_rule_groups( $rule_groups ) {
		$this->rule_groups = [];
		foreach ( $rule_groups as $rule_group ) {
			$this->add_rule_group( $rule_group );
		}

		return $this;
	}

	/**
	 * @param RuleGroup $rule
	 *
	 * @return $this
	 */
	public function add_rule_group( RuleGroup $rule ) {
		$this->rule_groups[] = $rule;

		return $this;
	}

	/**
	 * @return Action[]
	 */
	public function get_actions() {
		return $this->actions;
	}

	/**
	 * @param Action[] $actions
	 * @return $this
	 */
	public function set_actions( $actions ) {
		$this->actions = [];
		foreach ( $actions as $action ) {
			$this->add_action( $action );
		}

		return $this;
	}

	/**
	 * @param Action $action
	 * @return $this
	 */
	public function add_action( Action $action ) {
		$this->actions[] = $action;
		return $this;
	}

	/**
	 * @param string|int $index
	 * @return $this
	 */
	public function remove_action( $index ) {
		unset( $this->actions[ $index ] );
		return $this;
	}

	/**
	 * @return int
	 */
	public function get_order(): int {
		return $this->order;
	}

	/**
	 * @param int $order
	 *
	 * @return Workflow
	 */
	public function set_order( int $order ): Workflow {
		$this->order = $order;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_origin(): string {
		return $this->origin;
	}

	/**
	 * @param string $origin
	 *
	 * @return Workflow
	 */
	public function set_origin( string $origin ): Workflow {
		$this->origin = $origin;
		return $this;
	}

	/**
	 * Convert workflow data to an array.
	 *
	 * @since 6.0.10
	 *
	 * @return array
	 */
	public function to_array(): array {
		// Some options are stored together.
		$options = array_merge(
			[
				'click_tracking'      => $this->is_tracking_enabled(),
				'conversion_tracking' => $this->is_conversion_tracking_enabled(),
				'ga_link_tracking'    => $this->get_ga_link_tracking(),
			],
			$this->get_timing_options_data( $this->get_timing() )
		);

		// Main data for the workflow.
		$data = [
			'title'            => $this->get_title(),
			'status'           => $this->get_status(),
			'type'             => $this->get_type(),
			'is_transactional' => $this->is_transactional(),
			'order'            => $this->get_order(),
			'origin'           => $this->get_origin(),
			'options'          => $options,
			'trigger'          => $this->get_trigger() ? $this->get_trigger()->to_array() : [],
			'actions'          => [],
			'rules'            => [],
		];

		foreach ( $this->get_actions() as $action ) {
			$data['actions'][] = $action->to_array();
		}

		foreach ( $this->get_rule_groups() as $rule_group ) {
			$data['rules'][] = $rule_group->to_array();
		}

		if ( $this->get_id() ) {
			$data['id'] = $this->get_id();
		}

		return $data;
	}

	/**
	 * Get data from the Workflow timing object.
	 *
	 * @since 6.0.10
	 *
	 * @return array Normalized data from the timing object.
	 */
	private function get_timing_options_data(): array {
		$data = [
			'when_to_run' => $this->timing->get_type(),
		];

		switch ( get_class( $this->timing ) ) {
			case WorkflowTimingScheduled::class:
				/** @var WorkflowTimingScheduled $timing */
				$data['scheduled_time'] = $this->timing->get_scheduled_time();
				$data['scheduled_day']  = $this->timing->get_scheduled_days();
				// Deliberately skip break to ensure delay value and unit are set in the next clause.

			case WorkflowTimingDelayed::class:
				/** @var WorkflowTimingDelayed|WorkflowTimingScheduled $timing */
				$data['run_delay_value'] = $this->timing->get_delay_value();
				$data['run_delay_unit']  = $this->timing->get_delay_unit();
				break;

			case WorkflowTimingFixed::class:
				/** @var WorkflowTimingFixed $timing */
				$datetime           = $this->timing->get_fixed_datetime()->convert_to_site_time();
				$data['fixed_date'] = $datetime->format( 'Y-m-d' );
				$data['fixed_time'] = [
					$datetime->format( 'H' ),
					$datetime->format( 'i' ),
				];
				break;

			case WorkflowTimingVariable::class:
				/** @var WorkflowTimingVariable $timing */
				$data['queue_datetime'] = $this->timing->get_variable();
				break;

			case WorkflowTimingImmediate::class:
			default:
				// nothing to do here.
				break;
		}

		return $data;
	}
}

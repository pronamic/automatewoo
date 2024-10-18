<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Points_Rewards_Integration
 *
 * @since 4.5.0
 * @package AutomateWoo
 */
class Points_Rewards_Integration {

	/**
	 * Points_Rewards_Integration constructor.
	 */
	public function __construct() {
		add_filter( 'wc_points_rewards_event_description', [ $this, 'filter_points_modified_description' ], 10, 3 );
		add_filter( 'automatewoo/rules/includes', [ $this, 'add_points_rewards_rules' ], 10, 3 );
		add_filter( 'automatewoo/actions', [ $this, 'add_points_rewards_actions' ], 10, 3 );
		add_filter( 'automatewoo/variables', [ $this, 'add_points_rewards_variables' ], 10, 3 );
	}

	/**
	 * Get default event description
	 *
	 * @return string
	 */
	public static function get_default_event_description() {

		global $wc_points_rewards;
		$points_label = $wc_points_rewards->get_points_label( 0 );

		/* translators: Points label. */
		return sprintf( __( '%s modified by AutomateWoo', 'automatewoo' ), $points_label );
	}

	/**
	 * Modify event description.
	 *
	 * @param string $event_description
	 * @param string $event_type
	 * @param object $event
	 *
	 * @return string
	 */
	public function filter_points_modified_description( $event_description, $event_type, $event ) {

		if ( $event_type === 'automatewoo-adjustment' ) {
			if ( ! empty( $event->data['aw_description'] ) ) {
				$event_description = $event->data['aw_description'];
			} else {
				$event_description = self::get_default_event_description();
			}

			if ( is_admin() && isset( $event->data['workflow_id'] ) ) {
				$workflow_id = $event->data['workflow_id'];
				$url         = get_edit_post_link( $workflow_id );
				/* translators: %1$s: edit workflow URL, %2$s workflow ID */
				$event_description .= sprintf( __( ' (Workflow ID: <a href="%1$s">%2$s</a>)', 'automatewoo' ), $url, $workflow_id );
			}
		}

		return $event_description;
	}

	/**
	 * Add Rules.
	 *
	 * @param array $rule_paths
	 *
	 * @return array
	 */
	public function add_points_rewards_rules( $rule_paths ) {
		$rule_paths['points_rewards_customer_points'] = 'AutomateWoo\Rules\Points_Rewards_Customer_Points';
		return $rule_paths;
	}

	/**
	 * Add Actions.
	 *
	 * @param array $includes
	 *
	 * @return array
	 */
	public function add_points_rewards_actions( $includes ) {
		$includes['points_rewards_add_points']    = 'AutomateWoo\Action_Points_Rewards_Add_Points';
		$includes['points_rewards_remove_points'] = 'AutomateWoo\Action_Points_Rewards_Remove_Points';
		return $includes;
	}

	/**
	 * Add Points Variable.
	 *
	 * @param array $variable_paths
	 *
	 * @return array
	 */
	public function add_points_rewards_variables( $variable_paths ) {
		$variable_paths['customer']['points'] = Variables\Customer_Points::class;
		return $variable_paths;
	}
}

return new Points_Rewards_Integration();

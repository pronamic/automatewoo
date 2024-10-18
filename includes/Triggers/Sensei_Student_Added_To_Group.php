<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Student_Added_To_Group.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Student_Added_To_Group extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::SENSEI_GROUP, DataTypes::CUSTOMER ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Student Added To the Group', 'automatewoo' );
		$this->description = __( 'This trigger fires after students have been added to the group.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_pro_student_groups_group_student_added', array( $this, 'handle_group_student_added' ), 10, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$groups = Sensei_Workflow_Helper::get_groups_field();
		$this->add_field( $groups );
	}

	/**
	 * Handle Student added to the group.
	 *
	 * @param int $group_id Group ID.
	 * @param int $user_id  Added student ID.
	 */
	public function handle_group_student_added( $group_id, $user_id ) {
		$group = get_post( $group_id );
		$user  = get_user_by( 'id', $user_id );
		if ( ! $group || ! $user ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_groups = Clean::ids( $workflow->get_trigger_option( 'sensei_groups' ) );

			if ( ! empty( $sensei_groups ) && ! in_array( $group->ID, $sensei_groups, true ) ) {
				continue;
			}

			$workflow->maybe_run(
				[
					DataTypes::SENSEI_GROUP => $group,
					DataTypes::CUSTOMER     => Customer_Factory::get_by_user_id( $user_id ),
				]
			);
		}
	}
}

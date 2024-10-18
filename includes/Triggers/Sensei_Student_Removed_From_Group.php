<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Student_Removed_From_Group.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Student_Removed_From_Group extends Trigger {

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
		$this->title       = __( 'Student Removed From the Group', 'automatewoo' );
		$this->description = __( 'This trigger fires after students have been removed from the group.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_pro_student_groups_group_students_removed', array( $this, 'handle_group_students_removed' ), 10, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$groups = Sensei_Workflow_Helper::get_groups_field();
		$this->add_field( $groups );
	}

	/**
	 * Handle students removed from the group.
	 *
	 * @param int   $group_id    Group ID.
	 * @param int[] $student_ids Removed student IDs.
	 */
	public function handle_group_students_removed( $group_id, $student_ids ) {
		$group = get_post( $group_id );
		if ( ! $group || empty( $student_ids ) ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_groups = Clean::ids( $workflow->get_trigger_option( 'sensei_groups' ) );

			if ( ! empty( $sensei_groups ) && ! in_array( $group->ID, $sensei_groups, true ) ) {
				continue;
			}

			foreach ( $student_ids as $student_id ) {
				if ( empty( get_user_by( 'id', $student_id ) ) ) {
					continue;
				}

				$workflow->maybe_run(
					[
						DataTypes::SENSEI_GROUP => $group,
						DataTypes::CUSTOMER     => Customer_Factory::get_by_user_id( $student_id ),
					]
				);
			}
		}
	}
}

<?php
namespace AutomateWoo\Rules;

use AutomateWoo\Sensei_Workflow_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Sensei_Have_Not_Started_First_Lesson
 */
class Sensei_Have_Not_Started_First_Lesson extends Abstract_Sensei_Rule {

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();
		$this->title       = __( 'Course - Not Started the First Lesson', 'automatewoo' );
		$this->placeholder = __( 'Any Course', 'automatewoo' );
	}

	/**
	 * Validate the rule for a given Student.
	 *
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param array                 $course_ids
	 * @return bool
	 */
	public function validate( $customer, $compare, $course_ids ) {
		$passed  = true;
		$user_id = $customer->get_user_id();
		if ( ! $user_id || ! $this->is_sensei_workflow() ) {
			return false;
		}

		if ( empty( $course_ids ) ) {
			$course_ids = Sensei_Workflow_Helper::get_student_course_ids( $user_id );
		}

		if ( empty( $course_ids ) ) {
			return $passed;
		}

		foreach ( $course_ids as $course_id ) {
			$course_lessons = \Sensei()->course->course_lessons( $course_id );

			if ( $course_lessons && is_array( $course_lessons ) ) {
				$lesson = array_shift( $course_lessons );

				if ( \WooThemes_Sensei_Utils::user_started_lesson( $lesson->ID, $user_id ) ) {
					$passed = false;
					break;
				}
			}
		}

		return $passed;
	}
}

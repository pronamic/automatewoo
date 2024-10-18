<?php
namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Sensei_Workflow_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Sensei_Rule
 */
abstract class Abstract_Sensei_Rule extends Searchable_Select_Rule_Abstract {

	/**
	 * The rule's primary data item.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * This rule supports multiple selections.
	 *
	 * @var bool
	 */
	public $is_multi = true;

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->group         = Sensei_Workflow_Helper::get_group_name();
		$this->compare_types = [];
	}

	/**
	 * Get the ajax action to use for the AJAX search.
	 *
	 * @return string
	 */
	public function get_search_ajax_action() {
		return 'aw_json_search_sensei_courses';
	}

	/**
	 * Display the value title instead of the ID.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function get_object_display_value( $value ) {
		return get_the_title( $value );
	}

	/**
	 * Check whether the workflow is sensei related.
	 *
	 * @return boolean
	 */
	public function is_sensei_workflow() {
		$workflow = $this->get_workflow();
		if ( ! $workflow ) {
			return false;
		}

		return $workflow->get_trigger() && $workflow->get_trigger()->get_group() === $this->group;
	}

	/**
	 * Validate the rule for a given Student.
	 *
	 * @param \AutomateWoo\Customer $customer   The customer to validate the rule for.
	 * @param array                 $quiz_ids   Array of lesson ids
	 * @param string                $status     Quiz status. eg: 'passed', 'failed', 'not_yet_taken'.
	 * @return bool
	 */
	public function validate_quiz( $customer, $quiz_ids, $status ) {
		$passed  = true;
		$user_id = $customer->get_user_id();
		if ( ! $user_id || ! $this->is_sensei_workflow() ) {
			return false;
		}

		if ( empty( $quiz_ids ) ) {
			$quiz_ids = Sensei_Workflow_Helper::get_student_quiz_ids( $user_id );
		}

		if ( empty( $quiz_ids ) ) {
			return 'not_yet_taken' === $status;
		}

		foreach ( $quiz_ids as $quiz_id ) {
			$lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
			$quiz_progress = Sensei()->quiz_progress_repository->get( $quiz_id, $user_id );

			if ( 'not_yet_taken' === $status ) {
				if ( \Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
					return false;
				}
			} elseif ( 'passed' === $status || 'failed' === $status ) {
				if ( ! $quiz_progress || $quiz_progress->get_status() !== $status ) {
					return false;
				}
			}
		}

		return $passed;
	}
}

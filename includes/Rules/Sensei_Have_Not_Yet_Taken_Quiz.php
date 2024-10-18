<?php
namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Sensei_Have_Not_Yet_Taken_Quiz
 */
class Sensei_Have_Not_Yet_Taken_Quiz extends Abstract_Sensei_Rule {

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();
		$this->title       = __( 'Quiz - Not Yet Taken a Quiz', 'automatewoo' );
		$this->placeholder = __( 'Search for quizzes...', 'automatewoo' );
	}

	/**
	 * Get the ajax action to use for the AJAX search.
	 *
	 * @return string
	 */
	public function get_search_ajax_action() {
		return 'aw_json_search_sensei_quizzes';
	}

	/**
	 * Validate the rule for a given Student.
	 *
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param array                 $quiz_ids
	 * @return bool
	 */
	public function validate( $customer, $compare, $quiz_ids ) {
		return $this->validate_quiz( $customer, $quiz_ids, 'not_yet_taken' );
	}
}

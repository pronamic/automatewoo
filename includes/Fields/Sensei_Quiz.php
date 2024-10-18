<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable Sensei quiz field class.
 *
 * @since 5.6.10
 * @package AutomateWoo\Fields
 */
class Sensei_Quiz extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'sensei_quizzes';

	/**
	 * Product constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Quizzes', 'automatewoo' ) );
	}

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		return 'aw_json_search_sensei_quizzes';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		$quiz = get_post( $value );

		if ( $quiz ) {
			return get_the_title( $quiz );
		}

		return __( '(Quiz not found)', 'automatewoo' );
	}
}

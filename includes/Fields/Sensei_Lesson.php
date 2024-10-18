<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable Sensei lesson field class.
 *
 * @since 5.6.10
 * @package AutomateWoo\Fields
 */
class Sensei_Lesson extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'sensei_lessons';

	/**
	 * Product constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Lessons', 'automatewoo' ) );
	}

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		return 'aw_json_search_sensei_lessons';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		$lesson = get_post( $value );

		if ( $lesson ) {
			return get_the_title( $lesson );
		}

		return __( '(Lesson not found)', 'automatewoo' );
	}
}

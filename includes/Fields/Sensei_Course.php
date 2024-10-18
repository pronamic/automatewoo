<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable Sensei course field class.
 *
 * @since 5.6.10
 * @package AutomateWoo\Fields
 */
class Sensei_Course extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'sensei_courses';

	/**
	 * Product constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Courses', 'automatewoo' ) );
	}

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		return 'aw_json_search_sensei_courses';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		$course = get_post( $value );

		if ( $course ) {
			return get_the_title( $course );
		}

		return __( '(Course not found)', 'automatewoo' );
	}
}

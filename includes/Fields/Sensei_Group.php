<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Searchable Sensei group field class.
 *
 * @since 5.6.10
 * @package AutomateWoo\Fields
 */
class Sensei_Group extends Searchable_Select_Abstract {

	/**
	 * The default name for this field.
	 *
	 * @var string
	 */
	protected $name = 'sensei_groups';

	/**
	 * Sensei_Group constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_title( __( 'Groups', 'automatewoo' ) );
	}

	/**
	 * Get the ajax action to use for the search.
	 *
	 * @return string
	 */
	protected function get_search_ajax_action() {
		return 'aw_json_search_sensei_groups';
	}

	/**
	 * Get the displayed value of a selected option.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function get_select_option_display_value( $value ) {
		$group = get_post( $value );

		if ( $group ) {
			return get_the_title( $group );
		}

		return __( '(Group not found)', 'automatewoo' );
	}
}

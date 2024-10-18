<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class User_Tags
 */
class User_Tags extends Select {

	protected $name = 'user_tags';

	public $multiple = true;


	/**
	 * @param bool $show_placeholder
	 */
	function __construct( $show_placeholder = true ) {
		parent::__construct( $show_placeholder );

		$this->set_title( __( 'Tags', 'automatewoo' ) );

		$tags = get_terms([
			'taxonomy' => 'user_tag',
			'hide_empty' => false
		]);

		foreach ( $tags as $tag ) {
			$this->options[$tag->slug] = $tag->name;
		}
	}

}
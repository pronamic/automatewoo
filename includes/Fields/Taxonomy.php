<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Taxonomy
 */
class Taxonomy extends Select {

	protected $name = 'taxonomy';


	function __construct( $show_placeholder = true ) {
		parent::__construct( $show_placeholder );
		$this->set_title( __( 'Taxonomy', 'automatewoo' ) );
	}


	/**
	 * @return array
	 */
	function get_options() {

		$taxonomies = get_taxonomies( [], false );

		$exclude = [
			'action-group',
			'nav_menu',
			'post_format',
			'link_category',
			'category',
			'post_tag',
			'product_type',
			'product_shipping_class',
			'product_visibility',
		];

		$options = [];

		foreach( $taxonomies as $tax_slug => $taxonomy ) {

			if ( in_array($tax_slug, $exclude) )
				continue;

			$options[$tax_slug] = $taxonomy->labels->name;
		}

		$this->set_options( $options );

		return $this->options;
	}

}

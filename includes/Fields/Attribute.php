<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Attribute
 */
class Attribute extends Select {

	/** @var string */
	protected $name = 'attribute';

	/**
	 * @param bool $show_placeholder
	 */
	public function __construct( $show_placeholder = true ) {

		parent::__construct( $show_placeholder );

		$this->set_title( __( 'Attribute', 'automatewoo' ) );

		$attributes = wc_get_attribute_taxonomies();

		foreach ( $attributes as $attribute ) {
			$this->options[ $attribute->attribute_name ] = $attribute->attribute_label;
		}
	}
}

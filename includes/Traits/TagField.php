<?php

namespace AutomateWoo\Traits;

use AutomateWoo\Fields\Text;

/**
 * Trait Tag_Field
 *
 * Helper functions to create a tags field and parse comma-separated tags.
 *
 * @since 4.8.0
 */
trait TagField {

	/**
	 * Add a tags field to the action.
	 *
	 * @param string $name  (Optional) The name for the tag.
	 * @param string $title (Optional) The title to display for the tag.
	 *
	 * @return Text
	 */
	protected function get_tags_field( $name = null, $title = null ) {
		$name  = $name ?: 'tag';
		$title = $title ?: __( 'Tags', 'automatewoo' );

		$tag = ( new Text() )
			->set_name( $name )
			->set_title( $title )
			->set_variable_validation();

		return $tag;
	}

	/**
	 * Convert tags string to array.
	 *
	 * @param string $tags Comma-separated list of tags.
	 *
	 * @return array Array of tags.
	 */
	protected function parse_tags_field( $tags ) {
		return array_filter( array_map( 'trim', explode( ',', $tags ) ) );
	}

	/**
	 * Convert a string of tags to an array, with lowercase keys.
	 *
	 * @param string $tags Comma-separated list of tags.
	 *
	 * @return array Array of tags with lowercase versions as keys.
	 */
	protected function parse_tags_field_keys( $tags ) {
		$data = $this->parse_tags_field( $tags );

		return array_combine( array_map( 'strtolower', $data ), $data );
	}
}

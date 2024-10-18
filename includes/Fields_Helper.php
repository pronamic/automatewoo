<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Fields_Helper
 */
class Fields_Helper {

	/**
	 * @return array
	 */
	static function get_categories_list() {
		$list = [];

		$categories = get_terms( 'product_cat', [
			'orderby' => 'name',
			'hide_empty' => false
		]);

		foreach ( $categories as $category ) {
			$list[ $category->term_id ] = $category->name;
		}

		return $list;
	}


	/**
	 * @since 3.2.8
	 * @return array
	 */
	static function get_product_tags_list() {
		$list = [];

		$terms = get_terms( 'product_tag', [
			'orderby' => 'name',
			'hide_empty' => false
		]);

		foreach ( $terms as $term ) {
			$list[ $term->term_id ] = $term->name;
		}

		return $list;
	}

	/**
	 * @return array
	 */
	static function get_user_tags_list() {
		$list = [];

		$tags = get_terms([
			'taxonomy' => 'user_tag',
			'hide_empty' => false
		]);

		foreach ( $tags as $tag ) {
			$list[$tag->term_id] = $tag->name;
		}

		return $list;
	}

}

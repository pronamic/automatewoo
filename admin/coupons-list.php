<?php

use AutomateWoo\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class AW_Admin_Coupons_List
 */
class AW_Admin_Coupons_List {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'pre_get_posts', [ $this, 'modify_results' ] );
		add_filter( 'views_edit-shop_coupon', [ $this, 'filter_views' ] );
		add_filter( 'wp_count_posts', [ $this, 'filter_counts' ], 10, 2 );
	}


	/**
	 * Alters the table view for Edit Shop Coupons
	 *
	 * @param string[] $views An array of available list table views.
	 *
	 * @return string[] The filtered view
	 */
	public function filter_views( $views ) {

		$url = add_query_arg(
			[
				'post_type'          => 'shop_coupon',
				'filter_automatewoo' => '1',
			],
			admin_url( 'edit.php' )
		);

		$trash = aw_array_extract( $views, 'trash' );

		$count                = number_format_i18n( $this->get_count() );
		$views['automatewoo'] = '<a href="' . $url . '"' . ( aw_request( 'filter_automatewoo' ) ? 'class="current"' : '' ) . '>' . __( 'AutomateWoo', 'automatewoo' ) . ' <span class="count">(' . $count . ')</span></a>';

		if ( $trash ) {
			$views['trash'] = $trash;
		}

		return $views;
	}


	/**
	 * Alters WP Post Counts
	 *
	 * @param stdClass $counts An object containing the current post_type’s post counts by status.
	 * @param string   $type Post type.
	 *
	 * @return stdClass Filtered post counts.
	 */
	public function filter_counts( $counts, $type ) {

		if ( $type !== 'shop_coupon' ) {
			return $counts;
		}

		if ( ! isset( $counts->automatewoo ) ) {
			$count               = $this->get_count();
			$counts->publish    -= $count;
			$counts->automatewoo = $count;
		}

		return $counts;
	}


	/**
	 * Get the count of published AutomateWoo generated coupons.
	 *
	 * @return int Coupon count.
	 */
	public function get_count(): int {
		global $wpdb;

		$count = Cache::get( 'coupon_count', 'coupons' );
		if ( false === $count ) {
			$count = $wpdb->get_var(
				"SELECT DISTINCT COUNT(*) FROM `{$wpdb->posts}` AS posts
				INNER JOIN `{$wpdb->postmeta}` AS meta ON posts.ID = meta.post_id
				WHERE meta.meta_key = '_is_aw_coupon'
				AND meta_value = '1'
				AND posts.post_type = 'shop_coupon'
				AND posts.post_status = 'publish'"
			);
		}

		Cache::set( 'coupon_count', (int) $count, 'coupons' );

		return (int) $count;
	}


	/**
	 * Alter the results on pre gets posts. After the query variable object is created, but before the actual query is run.
	 *
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 */
	public function modify_results( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! isset( $query->query_vars['meta_query'] ) ) {
			$query->query_vars['meta_query'] = [];
		}

		if ( aw_request( 'filter_automatewoo' ) ) {
			$query->query_vars['meta_query'][] = [
				'key'   => '_is_aw_coupon',
				'value' => '1',
			];
		} elseif ( aw_request( 'post_status' ) === 'publish' ) {
			$query->query_vars['meta_query'][] = [
				'key'     => '_is_aw_coupon',
				'compare' => 'NOT EXISTS',
			];
		}
	}
}

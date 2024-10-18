<?php

namespace AutomateWoo;

use WP_Post;
use YITH_WCWL_Wishlist;

/**
 * @class Wishlists
 */
class Wishlists {

	/** @var array */
	public static $integration_options = [
		'yith'      => 'YITH Wishlists',
		'woothemes' => 'WooCommerce Wishlists',
	];


	/**
	 * @return string|false
	 */
	public static function get_integration() {
		if ( class_exists( 'WC_Wishlists_Plugin' ) ) {
			return 'woothemes';
		} elseif ( class_exists( 'YITH_WCWL' ) ) {
			return 'yith';
		} else {
			return false;
		}
	}


	/**
	 * @return string|false
	 */
	public static function get_integration_title() {
		$integration = self::get_integration();

		if ( ! $integration ) {
			return false;
		}

		return self::$integration_options[ $integration ];
	}


	/**
	 * Get wishlist by ID
	 *
	 * @param int $id
	 * @return bool|Wishlist
	 */
	public static function get_wishlist( $id ) {

		$integration = self::get_integration();

		if ( ! $id || ! $integration ) {
			return false;
		}

		if ( $integration === 'yith' ) {
			$wishlist = YITH_WCWL()->get_wishlist_detail( $id );
		} elseif ( $integration === 'woothemes' ) {
			$wishlist = get_post( $id );
		} else {
			return false;
		}

		return self::get_normalized_wishlist( $wishlist );
	}


	/**
	 * Convert wishlist objects from both integrations into the same format
	 * Returns false if wishlist is empty
	 *
	 * @param WP_Post|YITH_WCWL_Wishlist|array $wishlist
	 *
	 * @return Wishlist|false
	 */
	public static function get_normalized_wishlist( $wishlist ) {

		$integration = self::get_integration();

		if ( ! $wishlist || ! $integration ) {
			return false;
		}

		$normalized_wishlist = new Wishlist();

		if ( $integration === 'yith' ) {
			// Before v3.0 wishlists were arrays
			if ( is_array( $wishlist ) ) {
				$normalized_wishlist->id       = $wishlist['ID'];
				$normalized_wishlist->owner_id = $wishlist['user_id'];
			} elseif ( $wishlist instanceof YITH_WCWL_Wishlist ) {
				$normalized_wishlist->id       = $wishlist->get_id();
				$normalized_wishlist->owner_id = $wishlist->get_user_id();
			} else {
				return false;
			}
		} elseif ( $integration === 'woothemes' ) {

			if ( ! $wishlist instanceof WP_Post ) {
				return false;
			}

			$normalized_wishlist->id       = $wishlist->ID;
			$normalized_wishlist->owner_id = get_post_meta( $wishlist->ID, '_wishlist_owner', true );
		}

		return $normalized_wishlist;
	}


	/**
	 * Get an array with the IDs of all wishlists.
	 *
	 * @since 4.3.2
	 *
	 * @return array
	 */
	public static function get_all_wishlist_ids() {
		return self::get_wishlist_ids();
	}

	/**
	 * Get wishlist IDs.
	 *
	 * @since 4.5
	 *
	 * @param int|bool $limit
	 * @param int      $offset
	 *
	 * @return array
	 */
	public static function get_wishlist_ids( $limit = false, $offset = 0 ) {
		$integration = self::get_integration();
		$ids         = [];

		if ( $integration === 'woothemes' ) {
			$query = new \WP_Query(
				[
					'post_type'      => 'wishlist',
					'posts_per_page' => $limit === false ? -1 : $limit,
					'offset'         => $offset,
					'fields'         => 'ids',
				]
			);
			$ids   = $query->posts;
		} elseif ( $integration === 'yith' ) {
			$wishlists = YITH_WCWL()->get_wishlists(
				[
					// The query defaults to the current session and user IDs
					'user_id'    => false,
					'session_id' => false,
					'show_empty' => false,
					'limit'      => $limit === false ? false : $limit,
					'offset'     => $offset,
				]
			);

			foreach ( $wishlists as $wishlist ) {
				// Before v3.0 wishlists were arrays
				if ( is_array( $wishlist ) ) {
					$ids[] = $wishlist['ID'];
				} elseif ( $wishlist instanceof YITH_WCWL_Wishlist ) {
					$ids[] = $wishlist->get_id();
				}
			}
		}

		$ids = array_map( 'absint', $ids );
		return $ids;
	}
}

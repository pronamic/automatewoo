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
	 * Uses cursor-based pagination (ID > $after_id) when $after_id is provided
	 * to prevent skipping items when the dataset changes between async batch runs.
	 *
	 * @since 4.5
	 * @since 6.2.3 Added $after_id parameter for cursor-based pagination.
	 *
	 * @param int|bool $limit
	 * @param int      $offset    Legacy offset parameter. Prefer $after_id for cursor-based pagination.
	 * @param int      $after_id  Return wishlists with ID greater than this value.
	 *
	 * @return array
	 */
	public static function get_wishlist_ids( $limit = false, $offset = 0, $after_id = 0 ) {
		$integration = self::get_integration();
		$ids         = [];

		if ( $integration === 'woothemes' ) {
			$query_args = [
				'post_type'      => 'wishlist',
				'posts_per_page' => $limit === false ? -1 : $limit,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			];

			$where_filter = null;

			if ( $after_id > 0 ) {
				$where_filter = function ( $where ) use ( $after_id ) {
					global $wpdb;
					$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
					return $where;
				};
				add_filter( 'posts_where', $where_filter );
			} else {
				$query_args['offset'] = $offset;
			}

			try {
				$query = new \WP_Query( $query_args );
				$ids   = $query->posts;
			} finally {
				if ( $where_filter ) {
					remove_filter( 'posts_where', $where_filter );
				}
			}
		} elseif ( $integration === 'yith' ) {
			if ( $after_id > 0 ) {
				// Cursor mode: fetch all, filter by ID > $after_id, sort, apply limit.
				// YITH's API doesn't support cursor-based querying natively.
				$wishlists = YITH_WCWL()->get_wishlists(
					[
						'user_id'    => false,
						'session_id' => false,
						'show_empty' => false,
						'limit'      => false,
						'offset'     => 0,
					]
				);

				foreach ( $wishlists as $wishlist ) {
					if ( is_array( $wishlist ) ) {
						$ids[] = $wishlist['ID'];
					} elseif ( $wishlist instanceof YITH_WCWL_Wishlist ) {
						$ids[] = $wishlist->get_id();
					}
				}

				$ids = array_map( 'absint', $ids );
				$ids = array_filter(
					$ids,
					function ( $id ) use ( $after_id ) {
						return $id > $after_id;
					}
				);
				sort( $ids );

				if ( $limit !== false ) {
					$ids = array_slice( $ids, 0, $limit );
				}
			} else {
				// Offset mode: use YITH's native limit/offset (preserves WishlistItemOnSale behavior).
				$wishlists = YITH_WCWL()->get_wishlists(
					[
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

				$ids = array_map( 'absint', $ids );
			}

			return $ids;
		}

		$ids = array_map( 'absint', $ids );
		return $ids;
	}
}

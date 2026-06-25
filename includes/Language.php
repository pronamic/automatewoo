<?php

namespace AutomateWoo;

/**
 * Multi-lingual helper class
 *
 * @class Language
 */
class Language {

	/**
	 * @return bool
	 */
	public static function is_multilingual() {
		return Integrations::is_wpml() || Integrations::is_polylang();
	}


	/**
	 * @return string
	 */
	public static function get_default() {
		if ( Integrations::is_wpml() ) {
			return wpml_get_default_language();
		}

		if ( Integrations::is_polylang() && function_exists( 'pll_default_language' ) ) {
			return Clean::string( \pll_default_language() );
		}

		return '';
	}


	/**
	 * Returns empty string if multi-lingual is not enabled
	 *
	 * @return string
	 */
	public static function get_current() {
		if ( Integrations::is_wpml() ) {
			return wpml_get_current_language();
		}

		if ( Integrations::is_polylang() ) {
			return Clean::string( \pll_current_language() );
		}

		return '';
	}


	/**
	 * Set language back to original
	 */
	public static function set_original() {
		if ( Integrations::is_wpml() ) {
			self::set_current( ICL_LANGUAGE_CODE );
		}
	}


	/**
	 * @param string $language
	 */
	public static function set_current( $language ) {

		if ( ! self::is_multilingual() || ! $language ) {
			return;
		}

		if ( $language === self::get_current() ) {
			return; // no change required
		}

		if ( Integrations::is_wpml() ) {
			global $sitepress;
			$sitepress->switch_lang( $language, false );
		}
	}

	/**
	 * Get the language for a WordPress post object.
	 *
	 * @since 6.5.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_post_language( $post_id ) {
		// WPML always wins when active so running both plugins cannot mix language sources.
		if ( Integrations::is_wpml() ) {
			$info = wpml_get_language_information( null, $post_id );
			if ( is_array( $info ) ) {
				return Clean::string( $info['language_code'] );
			}

			return '';
		}

		if ( Integrations::is_polylang() && function_exists( 'pll_get_post_language' ) ) {
			return Clean::string( \pll_get_post_language( $post_id ) );
		}

		return '';
	}

	/**
	 * Get the language for an order.
	 *
	 * @since 6.5.0
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_language( $order ) {
		if ( ! $order ) {
			return '';
		}

		$order_lang = $order->get_meta( 'wpml_language' );
		if ( $order_lang ) {
			return Clean::string( $order_lang );
		}

		// Only consult Polylang here, and only when WPML is not active; WPML stores
		// historically rely on the 'wpml_language' order meta alone, so falling back to
		// a post language lookup would change behavior. Under HPOS without post sync the
		// order ID has no posts-table row, so this returns '' and language matching stays inert.
		if ( ! Integrations::is_wpml() && Integrations::is_polylang() && function_exists( 'pll_get_post_language' ) ) {
			return Clean::string( \pll_get_post_language( $order->get_id() ) );
		}

		return '';
	}

	/**
	 * Get a translated object ID.
	 *
	 * @since 6.5.0
	 *
	 * @param int    $object_id Object ID.
	 * @param string $type      Object type.
	 * @param string $language  Language code.
	 *
	 * @return int
	 */
	public static function get_translated_object_id( $object_id, $type, $language ) {
		if ( ! $object_id || ! $language ) {
			return $object_id;
		}

		if ( Integrations::is_wpml() && function_exists( 'icl_object_id' ) ) {
			return icl_object_id( $object_id, $type, true, $language );
		}

		if ( ! Integrations::is_wpml() && Integrations::is_polylang() && function_exists( 'pll_get_post' ) ) {
			$translated_id = \pll_get_post( $object_id, $language );
			return $translated_id ? absint( $translated_id ) : $object_id;
		}

		return $object_id;
	}

	/**
	 * Get translated post IDs for a post.
	 *
	 * @since 6.5.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public static function get_post_translation_ids( $post_id ) {
		if ( ! self::is_multilingual() ) {
			return [ $post_id ];
		}

		if ( Integrations::is_wpml() ) {
			global $sitepress;

			$ids          = [];
			$translations = $sitepress->get_element_translations( $post_id, 'post_post', false, true );

			if ( is_array( $translations ) ) {
				foreach ( $translations as $translation ) {
					$ids[] = $translation->element_id;
				}
			}

			$ids[] = $post_id; // sometimes wpml doesn't return default language id?

			return Clean::ids( $ids );
		}

		if ( Integrations::is_polylang() && function_exists( 'pll_get_post_translations' ) ) {
			// pll_get_post_translations() includes the post's own ID, so dedupe after adding it as a fallback.
			return array_values( array_unique( Clean::ids( array_merge( array_values( \pll_get_post_translations( $post_id ) ), [ $post_id ] ) ) ) );
		}

		return [ $post_id ];
	}


	/**
	 * Make language choice for guests and users persist
	 */
	public static function make_language_persistent() {

		if ( is_admin() || ! self::is_multilingual() ) {
			return;
		}

		$current_lang = self::get_current();

		if ( is_user_logged_in() ) {
			$user_lang = get_user_meta( get_current_user_id(), '_aw_persistent_language', true );

			if ( $user_lang !== $current_lang ) {
				self::set_user_language( get_current_user_id(), $current_lang );
			}
		} else {
			// Save language for guest if they have been stored
			$guest = Session_Tracker::get_current_guest();

			if ( $guest ) {
				if ( $guest->get_language() !== $current_lang ) {
					$guest->set_language( $current_lang );
					$guest->save();
				}
			}
		}
	}


	/**
	 * @param Order_Guest|\WP_User $user
	 * @return string|false
	 */
	public static function get_user_language( $user ) {

		if ( ! self::is_multilingual() ) {
			return false;
		}

		if ( $user instanceof \WP_User ) {
			$persisted = get_user_meta( $user->ID, '_aw_persistent_language', true );
			if ( $persisted ) {
				return Clean::string( $persisted );
			}
		}

		// guest orders, fetch the language from their order
		if ( is_a( $user, 'AutomateWoo\Order_Guest' ) && $user->order ) {
			$order_lang = self::get_order_language( $user->order );
			if ( $order_lang ) {
				return Clean::string( $order_lang );
			}
		}

		return self::get_default();
	}


	/**
	 * @param int    $user_id
	 * @param string $language
	 */
	public static function set_user_language( $user_id, $language ) {
		update_user_meta( $user_id, '_aw_persistent_language', $language );
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	public static function get_guest_language( $guest ) {

		if ( ! self::is_multilingual() ) {
			return '';
		}

		if ( $guest && $guest->get_language() ) {
			return $guest->get_language();
		}
		return self::get_default();
	}
}

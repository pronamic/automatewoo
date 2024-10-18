<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Multi-lingual helper class
 * @class Language
 */
class Language {


	/**
	 * @return bool
	 */
	static function is_multilingual() {
		return Integrations::is_wpml();
	}


	/**
	 * @return string
	 */
	static function get_default() {
		if ( Integrations::is_wpml() ) {
			return wpml_get_default_language();
		}

		return '';
	}


	/**
	 * Returns empty string if multi-lingual is not enabled
	 * @return string
	 */
	static function get_current() {
		if ( Integrations::is_wpml() ) {
			return wpml_get_current_language();
		}

		return '';
	}


	/**
	 * Set language back to original
	 */
	static function set_original() {
		if ( Integrations::is_wpml() ) {
			Language::set_current( ICL_LANGUAGE_CODE );
		}
	}


	/**
	 * @param $language
	 */
	static function set_current( $language ) {

		if ( ! Language::is_multilingual() || ! $language ) {
			return;
		}

		if ( $language == Language::get_current() ) {
			return; // no change required
		}

		if ( Integrations::is_wpml() ) {
			global $sitepress;
			$sitepress->switch_lang( $language, false );
		}
	}


	/**
	 * Make language choice for guests and users persist
	 */
	static function make_language_persistent() {

		if ( is_admin() || ! Language::is_multilingual() ) {
			return;
		}

		$current_lang = Language::get_current();

		if ( is_user_logged_in() ) {
			$user_lang = get_user_meta( get_current_user_id(), '_aw_persistent_language', true );

			if ( $user_lang != $current_lang ) {
				self::set_user_language( get_current_user_id(), $current_lang );
			}
		}
		else {
			// Save language for guest if they have been stored
			$guest = Session_Tracker::get_current_guest();

			if ( $guest ) {
				if ( $guest->get_language() != $current_lang ) {
					$guest->set_language( $current_lang );
					$guest->save();
				}
			}
		}
	}


	/**
	 * @param $user Order_Guest|\WP_User
	 * @return string|false
	 */
	static function get_user_language( $user ) {

		if ( ! Language::is_multilingual() )
			return false;

		if ( $user instanceof \WP_User ) {
			if ( $persisted = get_user_meta( $user->ID, '_aw_persistent_language', true ) ) {
				return Clean::string( $persisted );
			}
		}

		// guest orders, fetch the language from their order
		if ( is_a( $user, 'AutomateWoo\Order_Guest' ) && $user->order ) {
			if ( $order_lang = $user->order->get_meta( 'wpml_language' ) ) {
				return Clean::string( $order_lang );
			}
		}

		return wpml_get_default_language();
	}


	/**
	 * @param $user_id
	 * @param $language
	 */
	static function set_user_language( $user_id, $language ) {
		update_user_meta( $user_id, '_aw_persistent_language', $language );
	}


	/**
	 * @param Guest $guest
	 * @return string
	 */
	static function get_guest_language( $guest ) {

		if ( ! Language::is_multilingual() ) {
			return '';
		}

		if ( $guest && $guest->get_language() ) {
			return $guest->get_language();
		}
		return wpml_get_default_language();
	}


}

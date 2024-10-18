<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Communication_Account_Endpoint
 */
class Communication_Account_Tab {

	/** @var string */
	public static $endpoint;


	static function init() {
		$self = 'AutomateWoo\Communication_Account_Tab'; /** @var $self Communication_Account_Tab (for IDE) */

		self::$endpoint = apply_filters( 'automatewoo/communication_tab/endpoint', 'communication-preferences' );

		add_rewrite_endpoint( self::$endpoint, EP_PAGES );

		add_filter( 'query_vars', [ $self, 'add_query_vars' ], 0 );

		if ( Options::communication_account_tab_enabled() ) {
			add_filter( 'the_title', [ $self, 'endpoint_title' ] );
			add_filter( 'woocommerce_account_menu_items', [ $self, 'new_menu_items' ] );
			add_action( 'woocommerce_account_' . self::$endpoint .  '_endpoint', [ $self, 'endpoint_content' ] );
		}
	}


	/**
	 * @return string
	 */
	static function get_page_title() {
		return apply_filters( 'automatewoo/communication_tab/page_title', __( 'Communication preferences', 'automatewoo' ) );
	}


	/**
	 * @return string
	 */
	static function get_menu_title() {
		return apply_filters( 'automatewoo/communication_tab/menu_title', __( 'Communication', 'automatewoo' ) );
	}


	/**
	 * @param array $vars
	 * @return array
	 */
	static function add_query_vars( $vars ) {
		$vars[] = self::$endpoint;
		return $vars;
	}


	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param array $items
	 * @return array
	 */
	static function new_menu_items( $items ) {

		$logout_item = false;

		if ( isset( $items['customer-logout'] ) ) {
			$logout_item = $items['customer-logout'];
			unset( $items['customer-logout'] );
		}

		$items[ self::$endpoint ] = self::get_menu_title();

		if ( $logout_item ) {
			$items['customer-logout'] = $logout_item;
		}

		return $items;
	}


	/**
	 * Set endpoint title.
	 *
	 * @param string $title
	 * @return string
	 */
	static function endpoint_title( $title ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars[ self::$endpoint ] ) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() && is_user_logged_in() ) {
			$title = self::get_page_title();
			remove_filter( 'the_title', [ __CLASS__, 'endpoint_title' ] );
		}

		return $title;
	}


	/**
	 * Endpoint HTML content
	 */
	static function endpoint_content() {
		$customer = Customer_Factory::get_by_user_id( get_current_user_id() );
		Communication_Page::output_preferences_form( $customer );
	}


}

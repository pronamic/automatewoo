<?php
// phpcs:ignoreFile

/**
 * @class AutomateWoo_Legacy
 */
abstract class AutomateWoo_Legacy {

	/**
	 * @deprecated
	 * @var AutomateWoo\Admin
	 */
	public $admin;

	/**
	 * @var AutomateWoo\Session_Tracker
	 * @deprecated
	 */
	public $session_tracker;


	/**
	 * @return string
	 * @since 2.4.4
	 * @deprecated use WC_Geolocation::get_ip_address()
	 */
	function get_ip() {
		wc_deprecated_function( __METHOD__, '5.2.0', 'WC_Geolocation::get_ip_address' );

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}


	/**
	 * @deprecated
	 * @param $id
	 * @return AutomateWoo\Log|bool
	 */
	function get_log( $id ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'AutomateWoo\Log_Factory::get' );

		return AutomateWoo\Log_Factory::get( $id );
	}


	/**
	 * @deprecated
	 * @param $id
	 * @return AutomateWoo\Workflow|bool
	 */
	function get_workflow( $id ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'AutomateWoo\Workflows\Factory::get' );

		return \AutomateWoo\Workflows\Factory::get( $id );
	}


	/**
	 * @deprecated
	 * @param $id
	 * @return AutomateWoo\Queued_Event|bool
	 */
	function get_queued_event( $id ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'AutomateWoo\Queued_Event_Factory::get' );

		return AutomateWoo\Queued_Event_Factory::get( $id );
	}


	/**
	 * @deprecated
	 * @param $id
	 * @return AutomateWoo\Guest|bool
	 */
	function get_guest( $id ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'AutomateWoo\Guest_Factory::get' );

		return AutomateWoo\Guest_Factory::get( $id );
	}


	/**
	 * @deprecated
	 * @param $id
	 * @return AutomateWoo\Cart|bool
	 */
	function get_cart( $id ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'AutomateWoo\Cart_Factory::get' );

		return AutomateWoo\Cart_Factory::get( $id );
	}


}

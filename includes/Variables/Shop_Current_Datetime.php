<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Shop_Current_Datetime
 */
class Variable_Shop_Current_Datetime extends Variable_Abstract_Datetime {


	function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Current datetime as per your website's specified timezone.", 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * @param $parameters
	 * @return string
	 */
	function get_value( $parameters ) {
		return $this->format_datetime( current_time( 'mysql' ), $parameters );
	}
}

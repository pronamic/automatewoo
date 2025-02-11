<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Shop_Current_Datetime
 */
class Variable_Shop_Current_Datetime extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( "Current datetime as per your website's specified timezone.", 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $parameters ) {
		return $this->format_datetime( current_time( 'mysql' ), $parameters );
	}
}

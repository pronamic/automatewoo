<?php
namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Download_File_Name
 *
 * @since 5.6.6
 */
class Variable_Download_File_Name extends Variable {

	/**
	 * Set variable description.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the downloadable file name.', 'automatewoo' );
	}

	/**
	 * Get file name.
	 *
	 * @param Download $download
	 * @param array    $parameters
	 * @return string
	 */
	public function get_value( $download, $parameters ) {
		return $download->get_file_name();
	}
}

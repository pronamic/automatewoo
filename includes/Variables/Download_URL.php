<?php
namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Download_URL
 *
 * @since 5.6.6
 */
class Variable_Download_URL extends Variable {

	/**
	 * Set variable description.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the download URL.', 'automatewoo' );
	}

	/**
	 * Get Download URL.
	 *
	 * @param Download $download
	 * @param array    $parameters
	 * @return string
	 */
	public function get_value( $download, $parameters ) {
		return $download->get_download_url();
	}
}

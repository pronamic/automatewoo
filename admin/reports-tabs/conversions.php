<?php
defined( 'ABSPATH' ) || exit;

/**
 * AW_Reports_Tab_Conversions class.
 */
class AW_Reports_Tab_Conversions extends AW_Admin_Reports_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'conversions';
		$this->name = __( 'Conversions', 'automatewoo' );
	}

	/**
	 * Get report object.
	 *
	 * @return object
	 */
	public function get_report_class() {
		return new AutomateWoo\Report_Conversions();
	}
}

return new AW_Reports_Tab_Conversions();

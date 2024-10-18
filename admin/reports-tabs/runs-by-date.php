<?php
defined( 'ABSPATH' ) || exit;

/**
 * AW_Reports_Tab_Runs_By_Date class.
 */
class AW_Reports_Tab_Runs_By_Date extends AW_Admin_Reports_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'runs-by-date';
		$this->name = __( 'Workflows', 'automatewoo' );
	}

	/**
	 * Get report object.
	 *
	 * @return object
	 */
	public function get_report_class() {
		return new AutomateWoo\Report_Runs_By_Date();
	}
}

return new AW_Reports_Tab_Runs_By_Date();

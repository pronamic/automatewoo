<?php
defined( 'ABSPATH' ) || exit;

/**
 * AW_Reports_Tab_Email_Tracking class.
 */
class AW_Reports_Tab_Email_Tracking extends AW_Admin_Reports_Tab_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'email-tracking';
		$this->name = __( 'Email & SMS Tracking', 'automatewoo' );
	}

	/**
	 * Get report object.
	 *
	 * @return object
	 */
	public function get_report_class() {
		return new AutomateWoo\Report_Email_Tracking();
	}
}

return new AW_Reports_Tab_Email_Tracking();

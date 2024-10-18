<?php
/**
 * AW_Admin_Reports_Tab_Abstract class.
 *
 * @package     AutomateWoo/Admin
 */
abstract class AW_Admin_Reports_Tab_Abstract {

	/**
	 * Report tab ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Report tab name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Reports Controller object.
	 *
	 * @var AutomateWoo\Admin\Controllers\Reports
	 */
	public $controller;

	/**
	 * Get report object.
	 *
	 * @return object
	 */
	abstract public function get_report_class();

	/**
	 * Get report tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get report tab url.
	 *
	 * @return string
	 */
	public function get_url() {
		return admin_url( 'admin.php?page=automatewoo-reports&tab=' . $this->get_id() );
	}

	/**
	 * Output before report content.
	 *
	 * @return string|false
	 */
	public function output_before_report() {
		return false;
	}

	/**
	 * Report action handler.
	 *
	 * @param string $action
	 */
	public function handle_actions( $action ) {}

	/**
	 * Output report content.
	 */
	public function output() {

		$class = $this->get_report_class();

		if ( ! $class ) {
			return;
		}

		$class->nonce_action = $this->controller->get_nonce_action();

		$class->output_report();
	}
}

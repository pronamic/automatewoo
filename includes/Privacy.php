<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Privacy
 * @since 4.0
 */
class Privacy extends Privacy_Abstract {

	/**
	 * Init - hook into events.
	 */
	public function __construct() {
		parent::__construct( __( 'AutomateWoo', 'automatewoo' ) );

		// erasers
		$this->add_eraser( 'automatewoo-customer-logs', __( 'Workflow Logs', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'customer_workflow_logs' ] );
		$this->add_eraser( 'automatewoo-customer-queue', __( 'Queued Events', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'customer_workflow_queue' ] );
		$this->add_eraser( 'automatewoo-cart', __( 'Saved Cart', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'customer_cart' ] );
		$this->add_eraser( 'automatewoo-user-meta', __( 'User Meta', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'user_meta' ] );
		$this->add_eraser( 'automatewoo-user-tags', __( 'User Tags', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'user_tags' ] );
		// must be last
		$this->add_eraser( 'automatewoo-customer-object', __( 'AutomateWoo Customer Object', 'automatewoo' ), [ 'AutomateWoo\Privacy_Erasers', 'customer_and_guest_object' ] );

		// exporters
		$this->add_exporter( 'automatewoo-customer', __( 'Customer Object', 'automatewoo' ), [ 'AutomateWoo\Privacy_Exporters', 'customer_data' ] );
		$this->add_exporter( 'automatewoo-cart', __( 'Saved Cart', 'automatewoo' ), [ 'AutomateWoo\Privacy_Exporters', 'customer_cart' ] );
		$this->add_exporter( 'automatewoo-customer-logs', __( 'Workflow Logs', 'automatewoo' ), [ 'AutomateWoo\Privacy_Exporters', 'customer_workflow_logs' ] );
		$this->add_exporter( 'automatewoo-customer-queue', __( 'Queued Events', 'automatewoo' ), [ 'AutomateWoo\Privacy_Exporters', 'customer_workflow_queue' ] );

		do_action( 'automatewoo/privacy/loaded' );
	}


	/**
	 * Add suggested privacy policy content for the privacy policy page.
	 */
	public function get_privacy_message() {
		return Privacy_Policy_Guide::get_content();
	}


}

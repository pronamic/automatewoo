<?php
namespace AutomateWoo;

use AutomateWoo\Traits\MailServiceAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstraction extending from Action form implementing the Mailpoet actions.
 * All Mailpoet actions should extend this class.
 *
 * @class Action_Mailpoet_Abstract
 * @since 5.6.10
 */
abstract class Action_Mailpoet_Abstract extends Action {

	use MailServiceAction;

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		$this->group = __( 'MailPoet', 'automatewoo' );
	}
}

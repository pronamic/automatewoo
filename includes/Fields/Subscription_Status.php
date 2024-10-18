<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Subscription_Workflow_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Subscription_Status
 */
class Subscription_Status extends Select {

	/** @var string */
	protected $name = 'subscription_status';

	/**
	 * @param bool $allow_all
	 */
	public function __construct( $allow_all = true ) {
		parent::__construct( true );

		$this->set_title( __( 'Subscription status', 'automatewoo' ) );

		if ( $allow_all ) {
			$this->set_placeholder( __( '[Any]', 'automatewoo' ) );
		}

		$this->set_options( Subscription_Workflow_Helper::get_subscription_statuses() );
	}
}

<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Order_Status
 */
class Order_Status extends Select {

	/** @var string */
	protected $name = 'order_status';

	/**
	 * @param bool $allow_all
	 */
	public function __construct( $allow_all = true ) {
		parent::__construct( true );

		$this->set_title( __( 'Order status', 'automatewoo' ) );

		if ( $allow_all ) {
			$this->set_placeholder( __( '[Any]', 'automatewoo' ) );
		}

		$this->set_options( wc_get_order_statuses() );
	}
}

<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Order_Note_Type
 *
 * @since 3.5.0
 */
class Order_Note_Type extends Select {

	/**
	 * @param bool $show_placeholder
	 */
	public function __construct( $show_placeholder = true ) {
		parent::__construct( $show_placeholder );

		$this->set_name( 'note_type' );
		$this->set_title( __( 'Note type', 'automatewoo' ) );
		$this->set_options(
			[
				'customer' => __( 'Note to customer', 'automatewoo' ),
				'private'  => __( 'Private note', 'automatewoo' ),
			]
		);
	}
}

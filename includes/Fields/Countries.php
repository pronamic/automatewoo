<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Countries
 */
class Countries extends Select {

	/** @var string */
	protected $name = 'countries';

	/** @var bool */
	public $multiple = true;


	/**
	 * Countries constructor.
	 */
	public function __construct() {
		parent::__construct( false );
		$this->set_title( __( 'Countries', 'automatewoo' ) );
		$this->set_options( WC()->countries->get_allowed_countries() );
	}
}

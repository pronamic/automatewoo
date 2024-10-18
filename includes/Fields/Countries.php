<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Countries
 */
class Countries extends Select {

	protected $name = 'countries';

	public $multiple = true;


	function __construct() {
		parent::__construct( false );
		$this->set_title( __( 'Countries', 'automatewoo' ) );
		$this->set_options( WC()->countries->get_allowed_countries() );
	}

}

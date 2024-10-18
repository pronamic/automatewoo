<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Abandoned_Cart_Customer
 */
class Trigger_Abandoned_Cart_Customer extends Trigger_Abstract_Abandoned_Cart {

	public $supplied_data_items = [ 'customer', 'cart' ];


	function load_admin_details() {
		$this->title = __( 'Cart Abandoned', 'automatewoo' );
		$this->description = __( 'This trigger fires when a cart belonging to a registered customer or a guest customer is abandoned.', 'automatewoo' );
		parent::load_admin_details();
	}


}

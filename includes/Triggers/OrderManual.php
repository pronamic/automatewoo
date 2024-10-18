<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Data_Layer;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderManual
 *
 * @since   5.0.0
 * @package AutomateWoo
 */
class OrderManual extends AbstractManual {

	/**
	 * Set data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'order', 'customer' ];

	/**
	 * Get primary data type.
	 *
	 * @return string
	 */
	public function get_primary_data_type() {
		return 'order';
	}

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->title = __( 'Manual - Orders', 'automatewoo' );
	}

	/**
	 * Get data layer from primary data item.
	 *
	 * @param int $order
	 *
	 * @return Data_Layer|bool
	 */
	public function get_data_layer( $order ) {
		$order = wc_get_order( $order );

		if ( ! $order ) {
			return false;
		}

		return new Data_Layer(
			[
				'order'    => $order,
				'customer' => Customer_Factory::get_by_order( $order ),
			]
		);
	}
}

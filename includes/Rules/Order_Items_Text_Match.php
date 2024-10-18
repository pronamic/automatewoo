<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Items_Text_Match
 */
class Order_Items_Text_Match extends Abstract_String {

	public $data_item = 'order';


	function init() {
		$this->title = __( 'Order - Item Names - Text Match', 'automatewoo' );
		$this->compare_types = $this->get_multi_string_compare_types();
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		$names = [];

		foreach ( $order->get_items() as $item ) {
			$names[] = $item->get_name();
		}

		return $this->validate_string_multi( $names, $compare, $value );
	}
}

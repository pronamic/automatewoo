<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Items_Text_Match
 */
class Order_Items_Text_Match extends Abstract_String {

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title         = __( 'Order - Item Names - Text Match', 'automatewoo' );
		$this->compare_types = $this->get_multi_string_compare_types();
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$names = [];

		foreach ( $order->get_items() as $item ) {
			$names[] = $item->get_name();
		}

		return $this->validate_string_multi( $names, $compare, $value );
	}
}
